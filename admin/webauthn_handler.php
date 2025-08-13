<?php
// admin/webauthn_handler.php
session_start();
require_once __DIR__ . '/../config.php';

// In a real application, you would include the Composer autoloader here
// require_once __DIR__ . '/vendor/autoload.php';

header('Content-Type: application/json');

// --- Helper function to generate a random challenge ---
function generateChallenge() {
    return random_bytes(32);
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $postData = json_decode(file_get_contents('php://input'), true);
    $action = $_POST['action'] ?? ($postData['action'] ?? '');

    switch ($action) {
        // --- REGISTRATION ACTIONS ---
        case 'get_registration_challenge':
            if (!isset($_SESSION['admin_logged_in'])) {
                throw new Exception('Not authorized.');
            }
            
            $stmt = $pdo->prepare("SELECT id, username FROM admin_users WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception('User not found.');
            }
            
            $challenge = generateChallenge();
            $_SESSION['webauthn_challenge'] = $challenge;

            // These are the options the browser needs to create a credential
            $options = [
                'challenge' => base64url_encode($challenge),
                'rp' => ['name' => 'Kuru Exam Admin', 'id' => $_SERVER['SERVER_NAME']],
                'user' => [
                    'id' => base64url_encode((string)$user['id']), // User ID must be a string
                    'name' => $user['username'],
                    'displayName' => $user['username']
                ],
                'pubKeyCredParams' => [
                    ['type' => 'public-key', 'alg' => -7], // ES256
                    ['type' => 'public-key', 'alg' => -257] // RS256
                ],
                'authenticatorSelection' => [
                    'authenticatorAttachment' => 'platform',
                    'requireResidentKey' => true,
                    'userVerification' => 'required'
                ],
                'timeout' => 60000,
                'attestation' => 'direct'
            ];

            echo json_encode($options);
            break;

        case 'verify_registration':
             // DANGER: THIS IS A NON-FUNCTIONAL, INSECURE STUB
             // A real implementation requires a library to parse and verify the client data.
             
            if (!isset($_SESSION['admin_logged_in'])) {
                 throw new Exception('Not authorized.');
            }
            $credentialData = $postData['data'];
            
            // **SECURITY RISK:** In a real app, you would use a library to:
            // 1. Verify the challenge from the session against the one in clientDataJSON.
            // 2. Verify the origin (your domain).
            // 3. Parse the attestation object and verify the signature.
            // 4. Extract the public key and counter.

            // For this demo, we just save the data. THIS IS NOT SECURE.
            $stmt = $pdo->prepare("UPDATE admin_users SET webauthn_credential_id = ?, webauthn_public_key = ?, webauthn_credential_counter = ? WHERE id = ?");
            $stmt->execute([
                $credentialData['rawId'],
                json_encode($credentialData), // Storing the whole object for demo purposes
                0, // A real counter would come from the authenticatorData
                $_SESSION['admin_id']
            ]);

            echo json_encode(['verified' => true]);
            break;

        // --- LOGIN ACTIONS ---
        case 'get_login_challenge':
            $username = $_POST['username'] ?? '';
            if (empty($username)) throw new Exception('Username is required.');

            $stmt = $pdo->prepare("SELECT webauthn_credential_id FROM admin_users WHERE username = ? AND webauthn_credential_id IS NOT NULL");
            $stmt->execute([$username]);
            $credentialId = $stmt->fetchColumn();

            if (empty($credentialId)) {
                throw new Exception('No fingerprint credential found for this user.');
            }
            
            $challenge = generateChallenge();
            $_SESSION['webauthn_challenge'] = $challenge;

            $options = [
                'challenge' => base64url_encode($challenge),
                'allowCredentials' => [[
                    'type' => 'public-key',
                    'id' => $credentialId,
                    'transports' => ['internal']
                ]],
                'timeout' => 60000,
                'userVerification' => 'required'
            ];
            echo json_encode($options);
            break;

        case 'verify_login':
            // DANGER: THIS IS A NON-FUNCTIONAL, INSECURE STUB
            
            $assertion = $postData['data'];
            $credId = $assertion['rawId'];

            // 1. Find user by credential ID
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE webauthn_credential_id = ?");
            $stmt->execute([$credId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                 throw new Exception('Credential not recognized.');
            }
            
            // **SECURITY RISK:** A real implementation would:
            // 1. Fetch the user's stored public key and counter.
            // 2. Verify the session challenge against the one in clientDataJSON.
            // 3. Verify the origin.
            // 4. Use the public key to verify the signature from the assertion.
            // 5. Check and update the signature counter.

            // For this demo, we just assume it's valid if the credential ID exists.
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            echo json_encode(['verified' => true]);
            break;

        default:
            throw new Exception('Invalid action.');
    }

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

// Base64URL encoding function for PHP
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}