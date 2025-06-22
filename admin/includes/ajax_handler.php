<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
header('Content-Type: application/json'); // Set header immediately

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/functions.php';

// ====== START: WebAuthn Library Setup ======
$autoloader = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($autoloader)) {
    // If the autoloader is missing, die with a clean JSON error.
    die(json_encode([
        'success' => false,
        'error' => 'Server Configuration Error: The WebAuthn library is missing. Please run "composer require web-auth/webauthn-lib" in your project root.'
    ]));
}
require_once $autoloader;

use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\Server;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;

// Database configuration
$host = DB_HOST;
$dbname = DB_NAME;
$username = DB_USER;
$password = DB_PASS;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'error' => 'Database connection failed.']));
}

// Simple repository to manage credentials in our DB
class MyCredentialSourceRepository implements PublicKeyCredentialSourceRepository
{
    private $pdo;
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }
    public function findOneByCredentialId(string $credentialId): ?PublicKeyCredentialSource
    {
        // The credential ID from the DB is Base64 encoded, but the library expects the raw binary string.
        $stmt = $this->pdo->prepare("SELECT * FROM webauthn_credentials WHERE credential_id = ?");
        $stmt->execute([base64_encode($credentialId)]); // We store it base64 encoded, so we search for the same
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data)
            return null;
        // The library needs raw binary data, so we decode what we stored.
        $data['credential_id'] = base64_decode($data['credential_id']);
        return PublicKeyCredentialSource::createFromArray($data);
    }
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $userEntity): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM webauthn_credentials WHERE user_id = ?");
        $stmt->execute([$userEntity->getId()]);
        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $data) {
            if (isset($data['credential_id'])) {
                $data['credential_id'] = base64_decode($data['credential_id']);
                $results[] = PublicKeyCredentialSource::createFromArray($data);
            }
        }
        return $results;
    }
    public function saveCredentialSource(PublicKeyCredentialSource $source): void
    {
        // Store the credential ID in Base64 format for safe storage in text-based DB columns.
        $stmt = $this->pdo->prepare("INSERT INTO webauthn_credentials (user_id, credential_id, public_key, attestation_object) VALUES (?, ?, ?, ?)");
        $stmt->execute([$source->getUserHandle(), base64_encode($source->getPublicKeyCredentialId()), $source->getPublicKeyCredentialDescriptor(), $source->getAttestationObject()]);
    }
}

$publicKeyCredentialSourceRepository = new MyCredentialSourceRepository($pdo);
$rpEntity = new PublicKeyCredentialRpEntity('Kuru Exam', $_SERVER['HTTP_HOST'], null); // Changed App name
$server = new Server($rpEntity, $publicKeyCredentialSourceRepository);
// ====== END: WebAuthn Library Setup ======


$is_post_json = strpos($_SERVER["CONTENT_TYPE"] ?? '', "application/json") !== false;
$post_data = $is_post_json ? json_decode(file_get_contents('php://input'), true) : $_POST;
$action = $post_data['action'] ?? '';
$project_root = __DIR__ . '/../../';

// Allow WebAuthn actions without being logged in
$public_actions = ['start_webauthn_login', 'finish_webauthn_login'];
if (!isset($_SESSION['admin_logged_in']) && !in_array($action, $public_actions)) {
    die(json_encode(['success' => false, 'error' => 'Unauthorized access.']));
}


// ====== START: WebAuthn Action Handlers ======
if ($action === 'start_webauthn_registration') {
    $userEntity = new PublicKeyCredentialUserEntity($post_data['username'], $post_data['user_id'], $post_data['username']);
    $existing_credentials = $publicKeyCredentialSourceRepository->findAllForUserEntity($userEntity);
    $credentialCreationOptions = $server->generatePublicKeyCredentialCreationOptions($userEntity, null, $existing_credentials);
    $_SESSION['webauthn_creation_options'] = $credentialCreationOptions;
    echo json_encode(['success' => true, 'data' => $credentialCreationOptions]);
    exit;
}

if ($action === 'finish_webauthn_registration') {
    try {
        $publicKeyCredential = (new PublicKeyCredentialLoader())->loadArray($post_data['data']);
        $credentialSource = $server->loadAndCheckAttestationResponse($publicKeyCredential, $_SESSION['webauthn_creation_options']);
        $publicKeyCredentialSourceRepository->saveCredentialSource($credentialSource);
        unset($_SESSION['webauthn_creation_options']);
        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'start_webauthn_login') {
    $stmt = $pdo->prepare("SELECT id, username FROM admin_users WHERE username = ?");
    $stmt->execute([$post_data['username']]);
    $user = $stmt->fetch();
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'User not found or has no registered fingerprint.']);
        exit;
    }
    $userEntity = new PublicKeyCredentialUserEntity($user['username'], $user['id'], $user['username']);
    $allowed_credentials = $publicKeyCredentialSourceRepository->findAllForUserEntity($userEntity);
    if (empty($allowed_credentials)) {
        echo json_encode(['success' => false, 'error' => 'No fingerprint registered for this user.']);
        exit;
    }
    $credentialRequestOptions = $server->generatePublicKeyCredentialRequestOptions(null, $allowed_credentials);
    $_SESSION['webauthn_request_options'] = $credentialRequestOptions;
    $_SESSION['webauthn_user_id'] = $user['id'];
    $_SESSION['webauthn_username'] = $user['username'];
    echo json_encode(['success' => true, 'data' => $credentialRequestOptions]);
    exit;
}

if ($action === 'finish_webauthn_login') {
    try {
        $publicKeyCredential = (new PublicKeyCredentialLoader())->loadArray($post_data['data']);
        $server->loadAndCheckAssertionResponse($publicKeyCredential, $_SESSION['webauthn_request_options']);

        // Login successful! Set session.
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $_SESSION['webauthn_user_id'];
        $_SESSION['admin_username'] = $_SESSION['webauthn_username'];
        unset($_SESSION['webauthn_request_options'], $_SESSION['webauthn_user_id'], $_SESSION['webauthn_username']);
        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
// ====== END: WebAuthn Action Handlers ======

// Your existing AJAX handlers for AI, question editing, etc.
if ($action === 'analyze_question_with_ai') {
    // ... your existing code ...
}
// ... and so on for other actions

exit;

if ($action === 'analyze_question_with_ai') {
    $prompt = $_POST['prompt'] ?? '';
    if (empty($prompt)) {
        echo json_encode(['success' => false, 'error' => 'Prompt is empty.']);
        exit;
    }

    $responseJson = callGeminiAPI($prompt);
    $responseData = json_decode($responseJson, true);

    if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        $ai_response = $responseData['candidates'][0]['content']['parts'][0]['text'];
        echo json_encode(['success' => true, 'ai_response' => $ai_response]);
    } else {
        $error_details = isset($responseData['error']) ? print_r($responseData['error'], true) : 'No details provided.';
        echo json_encode(['success' => false, 'error' => 'Could not get a valid response from the AI. Details: ' . $error_details, 'raw_response' => $responseData]);
    }
    exit;
}

if ($action === 'get_question_for_edit') {
    $report_id = $_POST['report_id'];
    $stmt = $pdo->prepare("SELECT exam_id, question_id FROM error_report WHERE id = ?");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch();

    if (!$report) {
        echo json_encode(['success' => false, 'error' => 'Report not found.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT exam FROM course WHERE exam_code = ?");
    $stmt->execute([$report['exam_id']]);
    $exam_file_relative_path = $stmt->fetchColumn();
    $exam_file_full_path = $project_root . $exam_file_relative_path;

    if (!$exam_file_relative_path || !file_exists($exam_file_full_path)) {
        echo json_encode(['success' => false, 'error' => 'Exam file not found.']);
        exit;
    }

    $questions = json_decode(file_get_contents($exam_file_full_path), true);
    $found_question = null;
    foreach ($questions as $question) {
        if ($question['question_number'] == $report['question_id']) {
            $found_question = $question;
            break;
        }
    }

    if ($found_question) {
        echo json_encode(['success' => true, 'question' => $found_question]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Question with ID ' . $report['question_id'] . ' not found in file.']);
    }
}

if ($action === 'save_edited_question') {
    $report_id = $_POST['report_id'];
    $question_data = $_POST['question_data'];

    $stmt = $pdo->prepare("SELECT exam_id, question_id FROM error_report WHERE id = ?");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch();
    if (!$report) {
        echo json_encode(['success' => false, 'error' => 'Report not found.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT exam FROM course WHERE exam_code = ?");
    $stmt->execute([$report['exam_id']]);
    $exam_file_relative_path = $stmt->fetchColumn();
    $exam_file_full_path = $project_root . $exam_file_relative_path;

    if (!$exam_file_relative_path || !file_exists($exam_file_full_path)) {
        echo json_encode(['success' => false, 'error' => 'Exam file not found.']);
        exit;
    }

    $questions = json_decode(file_get_contents($exam_file_full_path), true);
    $question_index = -1;
    foreach ($questions as $index => $q) {
        if ($q['question_number'] == $report['question_id']) {
            $question_index = $index;
            break;
        }
    }

    if ($question_index !== -1) {
        $questions[$question_index]['question'] = $question_data['question'];
        $questions[$question_index]['options'] = $question_data['options'];
        $questions[$question_index]['correct_answer'] = $question_data['correct_answer'];
        $questions[$question_index]['explanation'] = $question_data['explanation'];

        file_put_contents($exam_file_full_path, json_encode($questions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $stmt = $pdo->prepare("DELETE FROM error_report WHERE id = ?");
        $stmt->execute([$report_id]);

        echo json_encode(['success' => true, 'message' => 'Question updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Could not find question to update.']);
    }
}

exit;
