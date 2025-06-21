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
$conn = getDBConnection();

// CHECK FOR AN ACTIVE, IN-PROGRESS EXAM SESSION
if (isset($_SESSION['questions']) && !empty($_SESSION['questions']) && isset($_SESSION['current_question'])) {
    // --- RESUME EXAM PATH ---
    // Load the confirmation page to ask the user if they want to resume.
    include 'templates/resume_confirmation.php';

} else {
    // --- NORMAL STARTUP PATH ---
    $page = $_GET['page'] ?? 'home'; 
    $error = '';
    $subjects = [];

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

    switch ($page) {
        case 'study_plans':
            include 'templates/study_plans.php';
            break;
        case 'movie':
            include 'templates/study_plans.php';
            break;
        case 'home':
        default:
            include 'templates/course_selection.php';
            break;
    }
}

$conn->close();
?>