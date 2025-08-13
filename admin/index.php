<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/functions.php';

// If the user is not logged in, redirect to the login page
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Connect to the database
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Include the form handler to process any form submissions
require_once __DIR__ . '/includes/form_handler.php';

// Determine the current page from the URL
$current_page = $_GET['page'] ?? 'dashboard';

// Include the header partial
include __DIR__ . '/partials/header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/partials/sidebar.php'; // Include the sidebar ?>

    <main class="main-content">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php
        // Load the content of the current page
        $page_path = __DIR__ . '/pages/' . $current_page . '.php';
        if (file_exists($page_path)) {
            include $page_path;
        } else {
            // If the page does not exist, show a 404 error
            echo "<h1>404 - Page Not Found</h1>";
        }
        ?>
    </main>
</div>

<?php
// Include the footer partial
include __DIR__ . '/partials/footer.php';
?>