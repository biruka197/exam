<?php
session_start();

// Include your database configuration
require_once __DIR__ . '/../config.php';

try {
    // Establish a database connection
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // If connection fails, stop the script and show an error.
    die("Database connection failed: " . $e->getMessage());
}

// If the user is already logged in, redirect them to the dashboard.
if (isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

$error_message = '';

// This block handles the traditional password login form submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT id, username, password FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        // Verify the user and the hashed password.
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            header('Location: index.php');
            exit();
        } else {
            $error_message = "Invalid username or password!";
        }
    } else {
        $error_message = "Please fill in all fields!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Exam System</title>
    <link rel="stylesheet" href="../assets/styles.css">
    <style>
        /* You can add these styles to your existing assets/styles.css or keep them here */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f4f7f6;
            font-family: Arial, sans-serif;
        }
        .login-container {
            background: #fff;
            padding: 2.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h1 {
            margin: 0;
            color: #333;
        }
        .login-header p {
            color: #777;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box; /* Important for padding */
        }
        .login-btn {
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: 4px;
            background-color: #007bff;
            color: white;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .login-btn:hover {
            background-color: #0056b3;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            margin-bottom: 1rem;
            text-align: center;
        }
        .divider {
            text-align: center;
            margin: 2rem 0;
            color: #aaa;
            display: flex;
            align-items: center;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e0e0e0;
        }
        .divider:not(:empty)::before { margin-right: 1em; }
        .divider:not(:empty)::after { margin-left: 1em; }

        #webauthn-status {
            font-size: 0.9em;
            color: #dc3545;
            text-align: center;
            min-height: 1.2em;
            margin-top: 1em;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Admin Login</h1>
            <p>Please sign in to continue</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input  type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="login-btn">Login with Password</button>
        </form>

        <div class="divider">OR</div>

        <div>
            <div class="form-group">
                <label for="webauthn_username">Username</label>
                <input type="text" id="webauthn_username" name="webauthn_username" value="Admin" placeholder="Enter username and tap below">
            </div>
            <button type="button" id="webauthn-login-btn" class="login-btn" style="background-color: #28a745;">
                <i class="fas fa-fingerprint"></i> Login with Fingerprint
            </button>
            <p id="webauthn-status"></p>
        </div>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js"></script>

    <script>
        const webauthnLoginBtn = document.getElementById('webauthn-login-btn');
        const webauthnUsernameInput = document.getElementById('webauthn_username');
        const webauthnStatusElem = document.getElementById('webauthn-status');

        // Helper functions to convert between ArrayBuffer and Base64URL
        function bufferToBase64url(buffer) {
            return btoa(String.fromCharCode.apply(null, new Uint8Array(buffer)))
                .replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
        }

        function base64urlToBuffer(base64url) {
            const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
            const raw = window.atob(base64);
            const buffer = new Uint8Array(raw.length);
            for (let i = 0; i < raw.length; i++) {
                buffer[i] = raw.charCodeAt(i);
            }
            return buffer;
        }

        // Attach click event listener to the fingerprint login button
        webauthnLoginBtn.addEventListener('click', async () => {
            const username = webauthnUsernameInput.value;
            if (!username) {
                webauthnStatusElem.textContent = 'Please enter your username first.';
                return;
            }
            webauthnStatusElem.textContent = 'Preparing secure login...';

            try {
                // 1. Get the login challenge from your PHP back-end
                const formData = new URLSearchParams({ action: 'get_login_challenge', username });
                const challengeResp = await fetch('webauthn_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData
                });
                const options = await challengeResp.json();
                if (options.error) throw new Error(options.error);

                // 2. Prepare the options for the browser's WebAuthn API
                options.challenge = base64urlToBuffer(options.challenge);
                options.allowCredentials.forEach(cred => {
                    cred.id = base64urlToBuffer(cred.id);
                });

                webauthnStatusElem.textContent = 'Please use your fingerprint sensor...';
                
                // 3. This triggers the browser/OS to show the fingerprint prompt
                const assertion = await navigator.credentials.get({ publicKey: options });

                // 4. Prepare the signed assertion to be verified by the server
                const assertionForServer = {
                    id: assertion.id,
                    rawId: bufferToBase64url(assertion.rawId),
                    type: assertion.type,
                    response: {
                        clientDataJSON: bufferToBase64url(assertion.response.clientDataJSON),
                        authenticatorData: bufferToBase64url(assertion.response.authenticatorData),
                        signature: bufferToBase64url(assertion.response.signature),
                        userHandle: assertion.response.userHandle ? bufferToBase64url(assertion.response.userHandle) : null,
                    },
                };
                
                webauthnStatusElem.textContent = 'Verifying login...';

                // 5. Send the signed assertion to the PHP back-end for final verification
                const verifyResp = await fetch('webauthn_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'verify_login', data: assertionForServer })
                });
                const verificationResult = await verifyResp.json();

                if (verificationResult.verified) {
                    webauthnStatusElem.textContent = 'Success! Redirecting to dashboard...';
                    window.location.href = 'index.php'; // On success, redirect to the admin area
                } else {
                    throw new Error(verificationResult.error || 'Server verification failed.');
                }

            } catch (err) {
                webauthnStatusElem.textContent = 'Login failed: ' + err.message;
            }
        });
    </script>
</body>
</html>