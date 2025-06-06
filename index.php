<?php
// Main Controller / Router

// 1. Load Configuration and Core Functions
require_once 'config.php';
require_once 'includes/db_connect.php';
require_once 'includes/ajax_handlers.php';

// 2. Route AJAX Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $conn = getDBConnection();
    handle_ajax_request($conn);
    $conn->close();
    exit;
}

// 3. Handle Initial Page Load (Standard GET Request)
$page = $_GET['page'] ?? 'home'; // Default page is now 'home'
$conn = getDBConnection();
$error = '';
$subjects = [];

// Fetch data only if we are on the home page
if ($page === 'home') {
    $result = $conn->query("SELECT DISTINCT course FROM course");
    if ($result === false) {
        error_log("Failed to fetch courses: " . $conn->error);
        $error = "Failed to load courses. Please check server logs.";
    } else {
        $course_names = [];
        while ($row = $result->fetch_assoc()) {
            $course_names[] = $row['course'];
        }
        $result->free();

        foreach ($course_names as $course_name) {
            $stmt = $conn->prepare("SELECT course_id, COUNT(exam_code) as exam_count FROM course WHERE course = ?");
            $stmt->bind_param("s", $course_name);
            $stmt->execute();
            $course_details_result = $stmt->get_result()->fetch_assoc();
            
            if ($course_details_result) {
                $subjects[$course_name] = [
                    'course' => $course_name,
                    'course_id' => $course_details_result['course_id'],
                    'exam_count' => $course_details_result['exam_count']
                ];
            }
            $stmt->close();
        }
    }
}

$conn->close();

// 4. Render the appropriate page template
// This now acts as a simple router for different pages.
switch ($page) {
    case 'study_plans':
        include 'templates/study_plans.php';
        break;
    case 'home':
    default:
        include 'templates/course_selection.php';
        break;
}