<?php
// Set a clear title for the diagnostic page.
$page_title = "WebAuthn Server Environment Checker";

// Define the minimum requirements for the web-auth/webauthn-lib.
// Based on your composer.json, you need PHP 8.2+.
define('MIN_PHP_VERSION', '8.2.0');
define('REQUIRED_EXTENSIONS', [
    'openssl',
    'mbstring',
    'json',
    'gmp',
    'bcmath',
    'pdo_mysql' // For database connectivity
]);

// A simple class to hold the results of our checks.
class StatusCheck {
    public $name;
    public $status;
    public $message;

    public function __construct(string $name, bool $status, string $message) {
        $this->name = $name;
        $this->status = $status;
        $this->message = $message;
    }

    public function isOk(): bool {
        return $this->status;
    }
}

// Array to hold all our diagnostic checks.
$checks = [];

// --- 1. Check PHP Version ---
$php_version_ok = version_compare(PHP_VERSION, MIN_PHP_VERSION, '>=');
$checks[] = new StatusCheck(
    'PHP Version Check',
    $php_version_ok,
    $php_version_ok
        ? 'Your PHP version is ' . PHP_VERSION . '. Great!'
        : 'FAIL: Your PHP version is ' . PHP_VERSION . '. The WebAuthn library requires PHP ' . MIN_PHP_VERSION . ' or newer. Please upgrade your server\'s PHP environment.'
);

// --- 2. Check for Composer Autoloader ---
$autoloader_path = __DIR__ . '/vendor/autoload.php';
$autoloader_ok = file_exists($autoloader_path);
$checks[] = new StatusCheck(
    'Composer Autoloader Check',
    $autoloader_ok,
    $autoloader_ok
        ? 'Found the Composer autoloader at <code>' . $autoloader_path . '</code>.'
        : 'FAIL: Composer autoloader not found at <code>' . $autoloader_path . '</code>. Please run <code>composer install</code> in your project root directory.'
);

// --- 3. Check Required PHP Extensions ---
if ($autoloader_ok) {
    // Only proceed if the autoloader exists.
    require_once $autoloader_path;

    foreach (REQUIRED_EXTENSIONS as $ext) {
        $extension_loaded = extension_loaded($ext);
        $checks[] = new StatusCheck(
            "PHP Extension: <code>{$ext}</code>",
            $extension_loaded,
            $extension_loaded
                ? "Extension <code>{$ext}</code> is installed and enabled."
                : "FAIL: The required PHP extension <code>{$ext}</code> is not enabled. Please edit your <code>php.ini</code> file to enable it."
        );
    }

    // --- 4. Check if WebAuthn Core Class Exists ---
    $webauthn_class = 'Webauthn\Server';
    $class_exists = class_exists($webauthn_class);
    $checks[] = new StatusCheck(
        'WebAuthn Library Class Loading',
        $class_exists,
        $class_exists
            ? "Successfully loaded the core WebAuthn class: <code>{$webauthn_class}</code>. The library is installed correctly."
            : 'FAIL: Could not load the core WebAuthn class: <code>' . $webauthn_class . '</code>. This might indicate a corrupted installation. Try running <code>composer update</code>.'
    );
} else {
    // Add placeholder checks if the autoloader is missing.
    $checks[] = new StatusCheck('PHP Extension Checks', false, 'Skipped because Composer autoloader was not found.');
    $checks[] = new StatusCheck('WebAuthn Library Class Loading', false, 'Skipped because Composer autoloader was not found.');
}

// --- 5. Final Overall Status ---
$all_ok = array_reduce($checks, fn($carry, $item) => $carry && $item->isOk(), true);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f7f9; margin: 0; padding: 2rem; }
        .container { max-width: 800px; margin: auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        h1 { color: #2c3e50; border-bottom: 2px solid #e1e5e9; padding-bottom: 0.5rem; }
        .status-box { padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 5px solid; }
        .status-box.success { background-color: #e8f5e9; border-color: #4CAF50; }
        .status-box.failure { background-color: #ffebee; border-color: #f44336; }
        .status-box h2 { margin-top: 0; }
        .checklist { list-style: none; padding: 0; }
        .checklist li { padding: 1rem; border-bottom: 1px solid #e1e5e9; display: flex; align-items: center; }
        .checklist li:last-child { border-bottom: none; }
        .checklist .icon { font-size: 1.5rem; margin-right: 1rem; }
        .checklist .pass .icon { color: #4CAF50; }
        .checklist .fail .icon { color: #f44336; }
        .checklist .message { flex: 1; }
        code { background-color: #e1e5e9; padding: 0.2em 0.4em; border-radius: 3px; font-family: "Courier New", Courier, monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo $page_title; ?></h1>

        <div class="status-box <?php echo $all_ok ? 'success' : 'failure'; ?>">
            <h2>Overall Status: <?php echo $all_ok ? 'All Checks Passed!' : 'One or More Checks Failed'; ?></h2>
            <p>
                <?php echo $all_ok
                    ? 'Your server environment appears to be correctly configured to run the WebAuthn library. You should be able to proceed without issues.'
                    : 'Your server environment does not meet all the requirements. Please review the failed checks below and follow the instructions to resolve them.'; ?>
            </p>
        </div>

        <h3>Detailed Checks</h3>
        <ul class="checklist">
            <?php foreach ($checks as $check): ?>
                <li class="<?php echo $check->isOk() ? 'pass' : 'fail'; ?>">
                    <span class="icon"><?php echo $check->isOk() ? '✔' : '✖'; ?></span>
                    <div class="message">
                        <strong><?php echo $check->name; ?>:</strong>
                        <p style="margin: 0.5rem 0 0 0;"><?php echo $check->message; ?></p>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>
