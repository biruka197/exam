<?php
// This function establishes and returns a database connection object.
function getDBConnection() {
    // Create a new mysqli connection using the constants from config.php
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check if the connection failed.
    if ($conn->connect_error) {
        // Log the detailed error to the server's error log.
        error_log("Database connection failed: " . $conn->connect_error);
        
        // Prepare a user-friendly error message.
        $error_message = "Connection failed: " . $conn->connect_error;

        // In debug mode, show the detailed error. In production, show a generic message.
        if (DEBUG_MODE) {
            die($error_message);
        } else {
            die(json_encode(['error' => 'Database connection failed. Please try again later.']));
        }
    }
    return $conn;
}