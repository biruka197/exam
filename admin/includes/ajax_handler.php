<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/functions.php'; // Ensure Gemini API function is available

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

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    die(json_encode(['success' => false, 'error' => 'Unauthorized access.']));
}

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $project_root = __DIR__ . '/../../';

    if ($action === 'analyze_question_with_ai') {
        // ... (existing code for AI analysis)
    }

    if ($action === 'get_question_for_edit') {
        // ... (existing code for getting question)
    }

    if ($action === 'save_edited_question') {
        // ... (existing code for saving question)
    }

    // NEW: Handle marking a report as invalid
    if ($action === 'mark_report_invalid') {
        $report_id = $_POST['report_id'];

        if (empty($report_id)) {
            echo json_encode(['success' => false, 'error' => 'Report ID is missing.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE error_report SET status = 'invalid' WHERE id = ?");
        $stmt->execute([$report_id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Report has been marked as invalid.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update report status. It might have been already updated or deleted.']);
        }
        exit;
    }


    // The following is existing code from the file, ensure it's present
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
    if ($action === 'get_online_users') {
        $stmt = $pdo->query("SELECT * FROM online_users WHERE last_seen > NOW() - INTERVAL 5 MINUTE");
        $online_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'users' => $online_users]);
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

            $stmt = $pdo->prepare("UPDATE error_report SET status = 'fixed' WHERE id = ?");
            $stmt->execute([$report_id]);

            echo json_encode(['success' => true, 'message' => 'Question updated and report marked as fixed.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Could not find question to update.']);
        }
    }

    exit;
}