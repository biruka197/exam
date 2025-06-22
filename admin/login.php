<?php
session_start();

// Database configuration
$host = '127.0.0.1';
$dbname = 'exam';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) { // Check for password to differentiate forms
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT id, username, password FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* [Existing CSS from your file] */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-container { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .login-header { text-align: center; margin-bottom: 2rem; }
        .login-header h1 { color: #333; margin-bottom: 0.5rem; }
        .login-header p { color: #666; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #333; font-weight: 600; }
        .form-group input { width: 100%; padding: 1rem; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 1rem; transition: border-color 0.3s ease; }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .login-btn { width: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 1rem; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: transform 0.2s ease; margin-top: 0.5rem; }
        .login-btn:hover { transform: translateY(-2px); }
        .login-btn:active { transform: translateY(0); }
        .alert-error { background: #fee; color: #c33; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #fcc; }
        .fingerprint-btn { background: #555; display: flex; align-items: center; justify-content: center; }
        .fingerprint-btn i { margin-right: 10px; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Admin Login</h1>
            <p>Please sign in to access the admin dashboard</p>
        </div>

        <div id="error-container">
        <?php if ($error_message): ?>
            <div class="alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        </div>

        <form method="POST" id="login-form">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="login-btn">Login</button>
        </form>
        
        <button type="button" class="login-btn fingerprint-btn" onclick="loginWithFingerprint()">
            <i class="fas fa-fingerprint"></i> Login with Fingerprint
        </button>

    </div>

<script>
// Helper functions from the previous script
function bufferDecode(value) {
    const s = atob(value.replace(/_/g, '/').replace(/-/g, '+'));
    const a = new Uint8Array(s.length);
    for (let i = 0; i < s.length; i++) { a[i] = s.charCodeAt(i); }
    return a;
}
function bufferEncode(value) {
    return btoa(String.fromCharCode.apply(null, new Uint8Array(value)))
        .replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
}

async function loginWithFingerprint() {
    const username = document.getElementById('username').value;
    const errorContainer = document.getElementById('error-container');
    errorContainer.innerHTML = '';

    if (!username) {
        errorContainer.innerHTML = '<div class="alert-error">Please enter your username first.</div>';
        return;
    }

    try {
        // 1. Get challenge from server
        const response = await fetch('includes/ajax_handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=start_webauthn_login&username=${username}`
        });
        const requestOptions = await response.json();
        if (!requestOptions.success) throw new Error(requestOptions.error);
        
        // 2. Decode options
        requestOptions.data.challenge = bufferDecode(requestOptions.data.challenge);
        requestOptions.data.allowCredentials.forEach(c => {
            c.id = bufferDecode(c.id);
        });

        // 3. Prompt user for fingerprint
        const credential = await navigator.credentials.get({ publicKey: requestOptions.data });

        // 4. Send signed challenge to server for verification
        const assertionResponse = {
            id: credential.id,
            rawId: bufferEncode(credential.rawId),
            type: credential.type,
            response: {
                authenticatorData: bufferEncode(credential.response.authenticatorData),
                clientDataJSON: bufferEncode(credential.response.clientDataJSON),
                signature: bufferEncode(credential.response.signature),
                userHandle: bufferEncode(credential.response.userHandle),
            },
        };

        const verificationResponse = await fetch('includes/ajax_handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'finish_webauthn_login',
                data: assertionResponse
            })
        });

        const verificationResult = await verificationResponse.json();

        if (verificationResult.success) {
            window.location.href = 'index.php'; // Redirect on success
        } else {
            throw new Error(verificationResult.error || 'Login with fingerprint failed.');
        }

    } catch (err) {
        console.error(err);
        errorContainer.innerHTML = `<div class="alert-error">${err.message}</div>`;
    }
}
</script>
</body>
</html>