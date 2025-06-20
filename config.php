<?php
// Check if a session is not already active before starting one.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- DEBUGGING AND ERROR REPORTING ---
// Set to 'true' for development to see all errors, 'false' for production.
define('DEBUG_MODE', true);
ini_set('display_errors', DEBUG_MODE ? 1 : 0);
ini_set('display_startup_errors', DEBUG_MODE ? 1 : 0);
error_reporting(E_ALL);

// --- DATABASE CREDENTIALS ---
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'exam');

// define('DB_HOST', 'localhost');
// define('DB_USER', 'kurumotm_exam1');
// define('DB_PASS', 'root123456');
// define('DB_NAME', 'kurumotm_exam1');