<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'your_database');

// Exams directory
define('EXAM_DIR', __DIR__ . '/exams/');

// Ensure exams directory exists
if (!is_dir(EXAM_DIR)) {
    mkdir(EXAM_DIR, 0755, true);
}

function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Function to shuffle question options and update correct_answer
function shuffleQuestionOptions(&$question) {
    if (!isset($question['options']) || !is_array($question['options']) || empty($question['options'])) {
        error_log("Invalid options array in question: " . json_encode($question));
        return;
    }

    $option_keys = array_keys($question['options']);
    $original_correct_answer = $question['correct_answer'];
    
    if (!isset($question['options'][$original_correct_answer])) {
        error_log("Correct answer '$original_correct_answer' not found in options: " . json_encode($question));
        return;
    }

    shuffle($option_keys);
    
    $shuffled_options = [];
    foreach ($option_keys as $new_key) {
        $shuffled_options[$new_key] = $question['options'][$new_key];
        if ($new_key === $original_correct_answer) {
            $question['correct_answer'] = $new_key;
        }
    }
    
    $question['options'] = $shuffled_options;
}

// Function to generate exam_code
function generateExamCode($conn, $course, &$used_numbers) {
    $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $course), 0, 3));
    if (strlen($prefix) < 3) {
        $prefix = str_pad($prefix, 3, '_');
    }
    $stmt = $conn->prepare("SELECT exam_code FROM course WHERE exam_code LIKE ? ORDER BY exam_code DESC LIMIT 1");
    $like_pattern = $prefix . '%';
    $stmt->bind_param("s", $like_pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $number = 1;
    if ($row = $result->fetch_assoc()) {
        $last_code = $row['exam_code'];
        $last_number = (int)substr($last_code, 3);
        $number = $last_number + 1;
    }
    // Ensure uniqueness within the current request
    while (in_array($number, $used_numbers)) {
        $number++;
    }
    $used_numbers[] = $number;
    $stmt->close();
    return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
}

// Function to get or generate course_id
function getOrGenerateCourseID($conn, $course) {
    // Check if course exists (case-insensitive)
    $stmt = $conn->prepare("SELECT course_id FROM course WHERE LOWER(course) = LOWER(?) LIMIT 1");
    $stmt->bind_param("s", $course);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        return $row['course_id'];
    }
    $stmt->close();
    
    // Generate new course_id
    $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $course));
    $timestamp = time();
    $course_id = $base . '_' . $timestamp;
    // Ensure uniqueness
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM course WHERE course_id = ?");
    $stmt->bind_param("s", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $stmt->close();
    if ($count > 0) {
        $course_id = $base . '_' . $timestamp . '_' . substr(md5(uniqid()), 0, 6);
    }
    return $course_id;
}

// Initialize variables
$page = $_GET['page'] ?? 'course_selection';
$errors = [];
$successes = [];
$subjects = [];
$total_questions = 0;
$selected_exams = [];
$selected_course = '';
$conn = getDBConnection();

// Admin authentication check
$is_admin_authenticated = isset($_SESSION['admin_id']);

// Handle admin login
if ($page === 'admin' && !$is_admin_authenticated && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($username) || empty($password)) {
        $errors[] = "Username and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT id, password FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['admin_id'] = $row['id'];
                $is_admin_authenticated = true;
                header("Location: index.php?page=admin");
                exit;
            } else {
                $errors[] = "Invalid username or password.";
            }
        } else {
            $errors[] = "Invalid username or password.";
        }
        $stmt->close();
    }
}

// Handle admin logout
if ($page === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    unset($_SESSION['admin_id']);
    header("Location: index.php?page=admin");
    exit;
}

// Restrict admin page access
if ($page === 'admin' && !$is_admin_authenticated) {
    $page = 'admin_login';
}

// Handle admin exam upload
if ($page === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_exam'])) {
    $course = trim($_POST['course'] ?? '');
    $files = $_FILES['exam_files'] ?? null;

    // Validate course
    if (empty($course)) {
        $errors[] = "Course name is required.";
    } elseif (!preg_match('/^[a-zA-Z0-9\s]+$/', $course)) {
        $errors[] = "Course name must be alphanumeric with spaces.";
    } elseif (empty($files) || !isset($files['name']) || count($files['name']) === 0) {
        $errors[] = "At least one exam file is required.";
    } else {
        // Get or generate course_id
        $course_id = getOrGenerateCourseID($conn, $course);
        
        // Track used exam code numbers
        $used_numbers = [];
        
        // Process each file
        for ($i = 0; $i < count($files['name']); $i++) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
            
            $filename = basename($file['name']);
            
            // Validate file
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "File '$filename': Upload error: " . $file['error'];
                continue;
            }
            if ($file['type'] !== 'application/json' || pathinfo($filename, PATHINFO_EXTENSION) !== 'json') {
                $errors[] = "File '$filename': Only JSON files are allowed.";
                continue;
            }
            
            // Validate JSON content
            $json_content = file_get_contents($file['tmp_name']);
            $questions = json_decode($json_content, true);
            if ($questions === null || !is_array($questions)) {
                $errors[] = "File '$filename': Invalid JSON format.";
                continue;
            }
            
            $is_valid = true;
            foreach ($questions as $q) {
                if (!isset($q['question_number'], $q['question'], $q['options'], $q['correct_answer'], $q['explanation']) ||
                    !is_array($q['options']) || empty($q['options']) || !isset($q['options'][$q['correct_answer']])) {
                    $is_valid = false;
                    break;
                }
            }
            if (!$is_valid) {
                $errors[] = "File '$filename': JSON file does not match required question format.";
                continue;
            }
            
            // Generate exam_code
            $exam_code = generateExamCode($conn, $course, $used_numbers);
            
            // Generate exam file path
            $exam_path = 'exams/' . $filename;
            $full_path = EXAM_DIR . $filename;
            
            // Check if file already exists
            if (file_exists($full_path)) {
                $errors[] = "File '$filename': Exam file already exists.";
                continue;
            }
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $full_path)) {
                // Insert into database
                $stmt = $conn->prepare("INSERT INTO course (course, exam, course_id, exam_code) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $course, $exam_path, $course_id, $exam_code);
                if ($stmt->execute()) {
                    $successes[] = "File '$filename': Exam uploaded successfully. Course ID: $course_id, Exam Code: $exam_code";
                } else {
                    error_log("File '$filename': Failed to insert exam into database: " . $stmt->error);
                    $errors[] = "File '$filename': Failed to save exam to database.";
                    unlink($full_path); // Remove uploaded file on failure
                }
                $stmt->close();
            } else {
                $errors[] = "File '$filename': Failed to upload exam file.";
            }
        }
    }
}

// Handle exam deletion
if ($page === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_exam'])) {
    $course_id = $_POST['course_id'] ?? '';
    if ($course_id) {
        // Fetch exam path to delete file
        $stmt = $conn->prepare("SELECT exam FROM course WHERE course_id = ?");
        $stmt->bind_param("s", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $exam_path = $row['exam'];
            $full_path = EXAM_DIR . basename($exam_path);
            // Delete from database
            $stmt = $conn->prepare("DELETE FROM course WHERE course_id = ?");
            $stmt->bind_param("s", $course_id);
            if ($stmt->execute()) {
                // Delete file if it exists
                if (file_exists($full_path)) {
                    unlink($full_path);
                }
                $successes[] = "Exam deleted successfully.";
            } else {
                error_log("Failed to delete exam from database: " . $stmt->error);
                $errors[] = "Failed to delete exam.";
            }
        } else {
            $errors[] = "Exam not found.";
        }
        $stmt->close();
    } else {
        $errors[] = "Invalid exam ID.";
    }
}

// Fetch subjects and count exams for course selection and settings pages
if ($page === 'course_selection' || $page === 'settings') {
    $result = $conn->query("SELECT course, course_id, exam, exam_code FROM course");
    if ($result === false) {
        error_log("Failed to fetch courses: " . $conn->error);
        $errors[] = "Failed to load courses.";
    } else {
        $all_courses = [];
        while ($row = $result->fetch_assoc()) {
            $all_courses[] = $row;
            $row['description'] = "Explore " . $row['course'] . " with comprehensive exams.";
            if (file_exists($row['exam'])) {
                $questions = json_decode(file_get_contents($row['exam']), true);
                $row['total_questions'] = ($questions !== null) ? count($questions) : 0;
            } else {
                error_log("Exam file not found: " . $row['exam']);
                $row['total_questions'] = 0;
            }
        }
        // Group by course and count exams
        foreach ($all_courses as $row) {
            $course_name = $row['course'];
            $exam_code = $row['exam_code'];
            $stmt = $conn->prepare("SELECT COUNT(*) as exam_count FROM course WHERE course = ? OR exam_code LIKE ?");
            $like_pattern = substr($exam_code, 0, 3) . '%';
            $stmt->bind_param("ss", $course_name, $like_pattern);
            $stmt->execute();
            $count_result = $stmt->get_result();
            $exam_count = $count_result->fetch_assoc()['exam_count'];
            $row['exam_count'] = $exam_count;
            $stmt->close();
            $subjects[$course_name] = $row; // Store one row per course
        }
    }
}

// Handle course selection to display exams
if ($page === 'course_selection' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_course'])) {
    $course = $_POST['course'] ?? '';
    $exam_code = $_POST['exam_code'] ?? '';
    if ($course && $exam_code) {
        $selected_course = $course;
        $stmt = $conn->prepare("SELECT course, course_id, exam, exam_code FROM course WHERE course = ? OR exam_code LIKE ?");
        $like_pattern = substr($exam_code, 0, 3) . '%';
        $stmt->bind_param("ss", $course, $like_pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                if (file_exists($row['exam'])) {
                    $questions = json_decode(file_get_contents($row['exam']), true);
                    $row['total_questions'] = ($questions !== null) ? count($questions) : 0;
                } else {
                    error_log("Exam file not found: " . $row['exam']);
                    $row['total_questions'] = 0;
                }
                $selected_exams[] = $row;
            }
        } else {
            error_log("No exams found for course: $course or exam_code: $exam_code");
            $errors[] = "No exams found for this course.";
        }
        $stmt->close();
    } else {
        $errors[] = "Please select a course.";
    }
}

// Handle exam selection to proceed to settings
if ($page === 'course_selection' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proceed'])) {
    $course_id = $_POST['course_id'] ?? '';
    if ($course_id) {
        $_SESSION['selected_course'] = $course_id;
        header("Location: index.php?page=settings");
        exit;
    } else {
        $errors[] = "Please select an exam.";
    }
}

// Fetch total questions for the selected course on the settings page
if ($page === 'settings' && isset($_SESSION['selected_course'])) {
    $stmt = $conn->prepare("SELECT exam FROM course WHERE course_id = ?");
    $stmt->bind_param("s", $_SESSION['selected_course']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $exam_file = $row['exam'];
        if (file_exists($exam_file)) {
            $all_questions = json_decode(file_get_contents($exam_file), true);
            if ($all_questions === null) {
                error_log("Failed to parse exam file: " . $exam_file);
                $errors[] = "Invalid exam file.";
            } else {
                $total_questions = count($all_questions);
            }
        } else {
            error_log("Exam file not found: " . $exam_file);
            $errors[] = "Exam file not found.";
        }
    } else {
        error_log("Course not found for course_id: " . $_SESSION['selected_course']);
        $errors[] = "Selected course not found.";
    }
    $stmt->close();
}

// Handle settings form submission
if ($page === 'settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = $_SESSION['selected_course'] ?? '';
    if (!$subject) {
        $errors[] = "No course selected.";
    } else {
        $num_questions = (int)($_POST['num_questions'] ?? 0);
        $range_start = (int)($_POST['range_start'] ?? 1);
        $range_end = (int)($_POST['range_end'] ?? 0);
        $order = $_POST['order'] ?? 'random';

        $stmt = $conn->prepare("SELECT exam FROM course WHERE course_id = ?");
        $stmt->bind_param("s", $subject);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $exam_file = $row['exam'];
            if (file_exists($exam_file)) {
                $all_questions = json_decode(file_get_contents($exam_file), true);
                if ($all_questions === null) {
                    error_log("Failed to parse exam file: " . $exam_file);
                    $errors[] = "Invalid exam file.";
                } else {
                    $total_questions = count($all_questions);

                    if ($range_start < 1 || $range_end > $total_questions || $range_start > $range_end) {
                        $errors[] = "Invalid question range. Please ensure the range is within 1 to $total_questions and start is less than or equal to end.";
                    } elseif ($num_questions > ($range_end - $range_start + 1)) {
                        $errors[] = "Number of questions exceeds the selected range.";
                    } else {
                        $filtered_questions = array_filter($all_questions, function($q) use ($range_start, $range_end) {
                            return $q['question_number'] >= $range_start && $q['question_number'] <= $range_end;
                        });
                        $filtered_questions = array_values($filtered_questions);

                        if ($order === 'random') {
                            shuffle($filtered_questions);
                        }
                        $questions = array_slice($filtered_questions, 0, $num_questions);

                        foreach ($questions as &$question) {
                            shuffleQuestionOptions($question);
                        }

                        $_SESSION['subject'] = $subject;
                        $_SESSION['num_questions'] = $num_questions;
                        $_SESSION['questions'] = $questions;
                        $_SESSION['start_time'] = time();
                        $_SESSION['timer_duration'] = $num_questions * 60;
                        $_SESSION['answers'] = [];
                        $_SESSION['current_question'] = 0;
                        $_SESSION['show_answer'] = [];
                        $_SESSION['timer_on'] = true;
                        header("Location: index.php?page=quiz");
                        exit;
                    }
                }
            } else {
                error_log("Exam file not found: " . $exam_file);
                $errors[] = "Exam file not found.";
            }
        } else {
            error_log("Course not found for course_id: " . $subject);
            $errors[] = "Selected course not found.";
        }
        $stmt->close();
    }
}

// Handle quiz page
if ($page === 'quiz') {
    if (!isset($_SESSION['questions']) || empty($_SESSION['questions'])) {
        error_log("No questions found in session for quiz page");
        header("Location: index.php?page=course_selection");
        exit;
    }

    $current_question = $_SESSION['current_question'] ?? 0;
    $questions = $_SESSION['questions'];
    $timer_duration = $_SESSION['timer_duration'] ?? 0;
    $elapsed_time = time() - ($_SESSION['start_time'] ?? time());
    $remaining_time = max(0, $timer_duration - $elapsed_time);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['exit'])) {
            session_destroy();
            header("Location: index.php?page=course_selection");
            exit;
        }
        if (isset($_POST['toggle_timer'])) {
            $_SESSION['timer_on'] = !($_SESSION['timer_on'] ?? true);
            $_SESSION['start_time'] = time();
            header("Location: index.php?page=quiz");
            exit;
        }
        if (isset($_POST['toggle_answer'])) {
            $_SESSION['show_answer'][$current_question] = !isset($_SESSION['show_answer'][$current_question]) || !$_SESSION['show_answer'][$current_question];
            header("Location: index.php?page=quiz");
            exit;
        }
        if (isset($_POST['prev'])) {
            if ($current_question > 0) {
                $_SESSION['current_question']--;
            }
            header("Location: index.php?page=quiz");
            exit;
        }
        if (isset($_POST['next'])) {
            $selected_option = $_POST['option'] ?? '';
            if ($selected_option) {
                $_SESSION['answers'][$current_question] = $selected_option;
            }
            if ($current_question < $_SESSION['num_questions'] - 1) {
                $_SESSION['current_question']++;
            } else {
                header("Location: index.php?page=result");
                exit;
            }
            header("Location: index.php?page=quiz");
            exit;
        }
    }

    if ($current_question >= count($questions)) {
        error_log("Current question index out of bounds: $current_question");
        header("Location: index.php?page=result");
        exit;
    }

    $timer_on = $_SESSION['timer_on'] ?? true;
    if ($timer_on && $remaining_time <= 0) {
        header("Location: index.php?page=result");
        exit;
    }

    $question = $questions[$current_question];
    $show_answer = isset($_SESSION['show_answer'][$current_question]) && $_SESSION['show_answer'][$current_question];
}

// Handle result page
if ($page === 'result') {
    if (!isset($_SESSION['questions']) || empty($_SESSION['questions'])) {
        error_log("No questions found in session for result page");
        header("Location: index.php?page=course_selection");
        exit;
    }

    $questions = $_SESSION['questions'];
    $answers = $_SESSION['answers'] ?? [];
    $score = 0;
    $total = count($questions);
    $incorrect_questions = [];

    foreach ($questions as $i => $question) {
        if (isset($answers[$i]) && $answers[$i] === $question['correct_answer']) {
            $score++;
        } else {
            $incorrect_questions[] = $question;
        }
    }

    $result_data = [
        'questions' => $questions,
        'answers' => $answers,
        'score' => $score,
        'total' => $total,
        'incorrect_questions' => $incorrect_questions
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['retake'])) {
        if (!empty($incorrect_questions)) {
            $selected_course = $_SESSION['selected_course'] ?? '';
            if (!$selected_course) {
                error_log("Selected course not found during retake");
                $errors[] = "Course context lost.";
            } else {
                shuffle($incorrect_questions);
                foreach ($incorrect_questions as &$question) {
                    shuffleQuestionOptions($question);
                }

                $_SESSION['questions'] = $incorrect_questions;
                $_SESSION['num_questions'] = count($incorrect_questions);
                $_SESSION['start_time'] = time();
                $_SESSION['timer_duration'] = $_SESSION['num_questions'] * 60;
                $_SESSION['answers'] = [];
                $_SESSION['current_question'] = 0;
                $_SESSION['show_answer'] = [];
                $_SESSION['timer_on'] = true;
                $_SESSION['selected_course'] = $selected_course;
                $_SESSION['subject'] = $selected_course;
                
                header("Location: index.php?page=quiz");
                exit;
            }
        } else {
            $errors[] = "No incorrect answers to retake.";
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restart'])) {
        session_destroy();
        header("Location: index.php?page=course_selection");
        exit;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuru Exam</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            background: linear-gradient(135deg, #f0f4ff, #e8f0ff);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            width: 100%;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            position: relative;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(to right, #006633, #004d2e);
            color: #fff;
            padding: 15px 20px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            font-weight: 600;
        }
        .header .timer-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .timer {
            font-size: 18px;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 10px;
            border-radius: 20px;
        }
        .timer-switch {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .timer-switch label {
            font-size: 14px;
            color: #fff;
        }
        .timer-switch .switch {
            position: relative;
            display: inline-block;
            width: 40px;
            height: 20px;
        }
        .timer-switch .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .timer-switch .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 20px;
        }
        .timer-switch .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        .timer-switch input:checked + .slider {
            background-color: #ff3333;
        }
        .timer-switch input:checked + .slider:before {
            transform: translateX(20px);
        }
        h2 {
            text-align: center;
            color: #1a1a1a;
            font-size: 28px;
            margin-bottom: 30px;
            font-weight: 600;
            position: relative;
        }
        h2::after {
            content: '';
            width: 50px;
            height: 3px;
            background: #006633;
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
        }
        .progress-bar {
            background: #e0e0e0;
            height: 8px;
            border-radius: 4px;
            margin: 15px 0;
            position: relative;
            overflow: hidden;
        }
        .progress {
            background: #006633;
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        .progress.answered {
            background: #ff3333;
        }
        .progress-text {
            text-align: center;
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        .question-nav {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }
        .question-nav button {
            background: #006633;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .question-nav button.active {
            background: #ffcc00;
            color: #000;
            transform: scale(1.1);
        }
        .question-nav button:hover {
            background: #004d2e;
        }
        .question-box {
            background: linear-gradient(145deg, #fff9e6, #fff3cd);
            padding: 25px;
            border-radius: 12px;
            margin: 20px 0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .question-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        .question-box .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .question-box .question-header p {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        .question-box .question-text {
            font-size: 20px;
            font-weight: 500;
            color: #1a1a1a;
            margin-bottom: 20px;
            line-height: 1.4;
        }
        .question-box .option {
            display: flex;
            align-items: center;
            background: #fff;
            padding: 12px 15px;
            border-radius: 8px;
            margin: 8px 0;
            transition: background 0.3s ease, transform 0.2s ease;
            cursor: pointer;
        }
        .question-box .option:hover {
            background: #e6f7e6;
            transform: translateX(5px);
        }
        .question-box .option input[type="radio"] {
            margin-right: 12px;
            accent-color: #006633;
        }
        .question-box .option label {
            font-size: 16px;
            color: #444;
            flex: 1;
            cursor: pointer;
        }
        .answer-box {
            background: #e6f7e6;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border-left: 4px solid #006633;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, opacity 0.3s ease;
            opacity: 0;
        }
        .answer-box.show {
            max-height: 200px;
            opacity: 1;
        }
        .answer-box p {
            font-size: 14px;
            color: #333;
            margin: 5px 0;
        }
        .answer-box .correct {
            color: #006633;
            font-weight: bold;
        }
        .nav-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            gap: 10px;
        }
        .nav-buttons button {
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .nav-buttons .prev {
            background: #006633;
            color: #fff;
        }
        .nav-buttons .next {
            background: #006633;
            color: #fff;
        }
        .nav-buttons .toggle-answer {
            background: #ffcc00;
            color: #000;
        }
        .nav-buttons button:hover {
            background: #004d2e;
            transform: translateY(-2px);
        }
        .nav-buttons .toggle-answer:hover {
            background: #e6b800;
        }
        .exit {
            background: #ff3333;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            margin-top: 10px;
            display: block;
            width: 150px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .exit:hover {
            background: #cc0000;
            transform: translateY(-2px);
        }
        .error, .success {
            text-align: left;
            font-size: 14px;
            margin: 10px 0;
            padding: 10px;
            border-radius: 8px;
        }
        .error {
            color: #ff3333;
            background: #ffe6e6;
        }
        .success {
            color: #006633;
            background: #e6f7e6;
        }
        .form-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .form-group div {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-size: 14px;
            color: #333;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group input, .form-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #006633;
            outline: none;
        }
        .total-questions {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .toggle-switch {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .toggle-switch label {
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 2px;
            bottom: 2px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #ff3333;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        .begin-btn, .retake-btn {
            background: linear-gradient(to right, #006633, #004d2e);
            color: #fff;
            padding: 12px;
            border: none;
            border-radius: 25px;
            width: 100%;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        .begin-btn:hover, .retake-btn:hover {
            background: linear-gradient(to right, #004d2e, #00331f);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .retake-btn {
            background: linear-gradient(to right, #ffcc00, #e6b800);
            color: #000;
        }
        .retake-btn:hover {
            background: linear-gradient(to right, #e6b800, #cc9900);
        }
        .question-review {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #006633;
        }
        .question-review.incorrect {
            border-left-color: #ff3333;
        }
        .question-review p {
            font-size: 14px;
            color: #444;
            margin: 5px 0;
        }
        .correct {
            color: #006633;
            font-weight: bold;
        }
        .incorrect {
            color: #ff3333;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-top: 20px;
        }
        a {
            color: #006633;
            text-decoration: none;
            font-weight: 500;
        }
        a:hover {
            text-decoration: underline;
        }
        .course-selection {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .course-card {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            border: 2px solid transparent;
        }
        .course-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
            border-color: #006633;
            background: linear-gradient(145deg, #f6ffed, #e6f7e6);
        }
        .course-card h3 {
            font-size: 20px;
            color: #1a1a1a;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .course-card .exam-count {
            font-size: 14px;
            color: #444;
            background: #ffcc00;
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 15px;
            font-weight: 500;
        }
        .course-card .icon {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 30px;
            color: #006633;
            opacity: 0.2;
            transition: opacity 0.3s ease;
        }
        .course-card:hover .icon {
            opacity: 0.4;
        }
        .exam-list {
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 12px;
            border-left: 5px solid #006633;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        .exam-list h3 {
            font-size: 20px;
            color: #1a1a1a;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .exam-list ul {
            list-style: none;
            padding: 0;
        }
        .exam-list li {
            font-size: 16px;
            color: #444;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.3s ease;
        }
        .exam-list li:hover {
            background: #e6f7e6;
        }
        .exam-list li:last-child {
            border-bottom: none;
        }
        .exam-list button {
            background: linear-gradient(to right, #006633, #004d2e);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .exam-list button:hover {
            background: linear-gradient(to right, #004d2e, #00331f);
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
        }
        .admin-form {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 5px solid #006633;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        .admin-form input[type="text"], .admin-form input[type="file"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 10px;
            width: 100%;
        }
        .admin-form input[type="file"] {
            padding: 5px;
        }
        .admin-form button {
            background: linear-gradient(to right, #006633, #004d2e);
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
        }
        .admin-form button:hover {
            background: linear-gradient(to right, #004d2e, #00331f);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .exam-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        .exam-table th, .exam-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .exam-table th {
            background: #ffcc00;
            color: #1a1a1a;
            font-weight: 600;
        }
        .exam-table tr:hover {
            background: #e6f7e6;
        }
        .exam-table button {
            background: #ff3333;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .exam-table button:hover {
            background: #cc0000;
            transform: translateY(-2px);
        }
        .login-form {
            max-width: 400px;
            margin: 0 auto;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 12px;
            border-left: 5px solid #006633;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        .login-form input[type="text"], .login-form input[type="password"] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 10px;
            width: 100%;
        }
        .login-form button {
            background: linear-gradient(to right, #006633, #004d2e);
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
        }
        .login-form button:hover {
            background: linear-gradient(to right, #004d2e, #00331f);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }
            .header h1 {
                font-size: 20px;
            }
            .timer {
                font-size: 16px;
            }
            .form-group {
                grid-template-columns: 1fr;
            }
            .question-nav button {
                width: 35px;
                height: 35px;
                font-size: 14px;
            }
            .nav-buttons {
                flex-direction: column;
                gap: 10px;
            }
            .nav-buttons button {
                width: 100%;
            }
            .course-selection {
                grid-template-columns: 1fr;
            }
            .course-card {
                padding: 20px;
            }
            .exam-list {
                padding: 15px;
            }
            .exam-table {
                font-size: 14px;
            }
            .exam-table th, .exam-table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($page === 'admin_login'): ?>
            <h2>Admin Login</h2>
            <form method="POST" action="index.php?page=admin" class="login-form">
                <div class="form-group">
                    <div>
                        <label for="username">Username</label>
                        <input type="text" name="username" id="username" required>
                    </div>
                    <div>
                        <label for="password">Password</label>
                        <input type="password" name="password" id="password" required>
                    </div>
                </div>
                <button type="submit" name="admin_login">Login</button>
            </form>
            <?php foreach ($errors as $error): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>

        <?php elseif ($page === 'admin'): ?>
            <h2>Manage Exams</h2>
            <?php foreach ($successes as $success): ?>
                <p class="success"><?php echo htmlspecialchars($success); ?></p>
            <?php endforeach; ?>
            <?php foreach ($errors as $error): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
            <form method="POST" enctype="multipart/form-data" class="admin-form">
                <div class="form-group">
                    <div>
                        <label for="course">Course Name</label>
                        <input type="text" name="course" id="course" placeholder="e.g., Physics" required>
                    </div>
                    <div>
                        <label for="exam_files">Exam JSON Files</label>
                        <input type="file" name="exam_files[]" id="exam_files" accept=".json" multiple required>
                    </div>
                </div>
                <button type="submit" name="upload_exam">Upload Exams</button>
            </form>
            <h3>Existing Exams</h3>
            <?php
            $conn = getDBConnection();
            $result = $conn->query("SELECT course, exam, course_id, exam_code FROM course ORDER BY course");
            if ($result->num_rows > 0):
            ?>
                <table class="exam-table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Exam File</th>
                            <th>Course ID</th>
                            <th>Exam Code</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['course']); ?></td>
                                <td><?php echo htmlspecialchars($row['exam']); ?></td>
                                <td><?php echo htmlspecialchars($row['course_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['exam_code']); ?></td>
                                <td>
                                    <form method="POST" action="index.php?page=admin">
                                        <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($row['course_id']); ?>">
                                        <button type="submit" name="delete_exam">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No exams found.</p>
            <?php endif; ?>
            <?php $conn->close(); ?>
            <div class="nav-buttons">
                <form method="POST" action="index.php?page=admin">
                    <button type="submit" name="logout" class="exit">Logout</button>
                </form>
                <a href="index.php?page=course_selection" class="begin-btn">Back to Course Selection</a>
            </div>

        <?php elseif ($page === 'course_selection'): ?>
            <h2>Choose Your Course</h2>
            <div class="course-selection">
                <?php foreach ($subjects as $subject): ?>
                    <form method="POST" action="index.php?page=course_selection">
                        <input type="hidden" name="course" value="<?php echo htmlspecialchars($subject['course']); ?>">
                        <input type="hidden" name="exam_code" value="<?php echo htmlspecialchars($subject['exam_code']); ?>">
                        <button type="submit" name="select_course" class="course-card">
                            <span class="icon">📚</span>
                            <h3><?php echo htmlspecialchars($subject['course']); ?></h3>
                            <span class="exam-count"><?php echo $subject['exam_count']; ?> Exams</span>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($selected_exams) && $selected_course): ?>
                <div class="exam-list">
                    <h3>Available Exams for <?php echo htmlspecialchars($selected_course); ?></h3>
                    <ul>
                        <?php foreach ($selected_exams as $exam): ?>
                            <li>
                                <span><?php echo htmlspecialchars(basename($exam['exam'], '.json')); ?> (<?php echo $exam['total_questions']; ?> Questions)</span>
                                <form method="POST" action="index.php?page=course_selection">
                                    <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($exam['course_id']); ?>">
                                    <button type="submit" name="proceed">Select Exam</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php foreach ($errors as $error): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
            <div class="nav-buttons">
                <a href="index.php?page=admin" class="begin-btn">Admin Panel</a>
            </div>

        <?php elseif ($page === 'settings'): ?>
            <?php if (!isset($_SESSION['selected_course'])): ?>
                <?php header("Location: index.php?page=course_selection"); exit; ?>
            <?php endif; ?>
            <h2>Examination Settings</h2>
            <form method="POST" action="index.php?page=settings">
                <div class="form-group">
                    <div>
                        <label>Number of Questions</label>
                        <input type="number" name="num_questions" value="3" min="1" required>
                    </div>
                    <div>
                        <label>Question Range Start</label>
                        <input type="number" name="range_start" value="1" min="1" required>
                        <span class="total-questions">Total Questions: <?php echo $total_questions; ?></span>
                    </div>
                    <div>
                        <label>Question Range End</label>
                        <input type="number" name="range_end" value="3" min="1" required>
                        <span class="total-questions">Total Questions: <?php echo $total_questions; ?></span>
                    </div>
                </div>
                <div class="toggle-switch">
                    <label>Question Order</label>
                    <label class="switch">
                        <input type="checkbox" name="order" value="in_order" <?php echo (isset($_POST['order']) && $_POST['order'] === 'in_order') ? 'checked' : ''; ?>>
                        <span class="slider round"></span>
                    </label>
                    <span>In Order</span>
                </div>
                <button type="submit" class="begin-btn">Begin Examination</button>
            </form>
            <?php foreach ($errors as $error): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>

        <?php elseif ($page === 'quiz'): ?>
            <div class="header">
                <h1>Kuru Exam</h1>
                <div class="timer-controls">
                    <span class="timer" id="timer"><?php echo $timer_on ? gmdate("i:s", $remaining_time) : '--:--'; ?></span>
                    <div class="timer-switch">
                        <label class="switch">
                            <input type="checkbox" id="timer-toggle" onchange="document.forms[0].elements['toggle_timer'].click();" <?php echo $timer_on ? 'checked' : ''; ?>>
                        <span class="slider round"></span>
                    </label>
                    <label for="timer-toggle">Timer</label>
                </div>
            </div>
            <div class="progress-text">Progress: <?php echo count(array_filter($_SESSION['answers'])) . ' of ' . $_SESSION['num_questions']; ?> answered</div>
            <div class="progress-bar">
                <div class="progress answered" style="width: <?php echo (count(array_filter($_SESSION['answers'])) / $_SESSION['num_questions']) * 100; ?>%;"></div>
                <div class="progress" style="width: <?php echo (($current_question + 1) / $_SESSION['num_questions']) * 100; ?>%;"></div>
            </div>
            <div class="question-nav">
                <?php for ($i = 0; $i < $_SESSION['num_questions']; $i++): ?>
                    <button type="button" class="<?php echo $i === $current_question ? 'active' : ''; ?>" onclick="navigateTo(<?php echo $i; ?>)"><?php echo $i + 1; ?></button>
                <?php endfor; ?>
            </div>
            <div class="question-box">
                <div class="question-header">
                    <p>Question <?php echo $current_question + 1; ?> of <?php echo $_SESSION['num_questions']; ?></p>
                </div>
                <div class="question-text"><?php echo htmlspecialchars($question['question']); ?></div>
                <form method="POST" action="index.php?page=quiz">
                    <?php foreach ($question['options'] as $key => $option): ?>
                        <div class="option">
                            <input type="radio" name="option" value="<?php echo htmlspecialchars($key); ?>" id="option-<?php echo $key; ?>" <?php echo (isset($_SESSION['answers'][$current_question]) && $_SESSION['answers'][$current_question] === $key) ? 'checked' : ''; ?>>
                            <label for="option-<?php echo $key; ?>"><?php echo htmlspecialchars($option); ?></label>
                        </div>
                    <?php endforeach; ?>
                    <div class="nav-buttons">
                        <button type="submit" name="prev" class="prev">Previous</button>
                        <button type="submit" name="toggle_answer" class="toggle-answer"><?php echo $show_answer ? 'Hide Answer' : 'Show Answer'; ?></button>
                        <button type="submit" name="next" class="next">Next</button>
                        <button type="submit" name="toggle_timer" style="display: none;"></button>
                    </div>
                    <button type="submit" name="exit" class="exit">Exit Exam</button>
                </form>
                <div class="answer-box <?php echo $show_answer ? 'show' : ''; ?>">
                    <p><strong>Correct Answer:</strong> <span class="correct"><?php echo htmlspecialchars($question['options'][$question['correct_answer']]); ?></span></p>
                    <p><strong>Explanation:</strong> <?php echo htmlspecialchars($question['explanation']); ?></p>
                </div>
            </div>
            <script>
                let timeLeft = <?php echo $remaining_time; ?>;
                let timerOn = <?php echo $timer_on ? 'true' : 'false'; ?>;
                function updateTimer() {
                    if (!timerOn) {
                        document.getElementById('timer').textContent = '--:--';
                        return;
                    }
                    const timer = document.getElementById('timer');
                    if (timeLeft <= 0) {
                        <?php if ($timer_on): ?>
                            document.forms[0].submit();
                        <?php endif; ?>
                        return;
                    }
                    let minutes = Math.floor(timeLeft / 60);
                    let seconds = timeLeft % 60;
                    timer.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    timeLeft--;
                    setTimeout(updateTimer, 1000);
                }
                function navigateTo(index) {
                    document.forms[0].elements['next'].click();
                    window.location.href = 'index.php?page=quiz&navigate=' + index;
                }
                <?php if (isset($_GET['navigate'])): ?>
                    <?php $_SESSION['current_question'] = (int)$_GET['navigate']; ?>
                    window.location.href = 'index.php?page=quiz';
                <?php endif; ?>
                window.onload = updateTimer;
            </script>

        <?php elseif ($page === 'result'): ?>
            <h2>Exam Result</h2>
            <p>Your Score: <?php echo $result_data['score']; ?> / <?php echo $result_data['total']; ?></p>
            <p>Percentage: <?php echo round(($result_data['score'] / $result_data['total']) * 100, 2); ?>%</p>
            <h2>Review</h2>
            <?php foreach ($result_data['questions'] as $i => $question): ?>
                <div class="question-review <?php echo (isset($result_data['answers'][$i]) && $result_data['answers'][$i] === $question['correct_answer']) ? '' : 'incorrect'; ?>">
                    <p><strong>Question <?php echo $question['question_number']; ?>:</strong> <?php echo htmlspecialchars($question['question']); ?></p>
                    <p><strong>Your Answer:</strong> <?php echo isset($result_data['answers'][$i]) ? htmlspecialchars($question['options'][$result_data['answers'][$i]]) : 'Not answered'; ?></p>
                    <p><strong>Correct Answer:</strong> <?php echo htmlspecialchars($question['options'][$question['correct_answer']]); ?></p>
                    <p><strong>Explanation:</strong> <?php echo htmlspecialchars($question['explanation']); ?></p>
                    <p><strong>Result:</strong>
                        <?php echo isset($result_data['answers'][$i]) && $result_data['answers'][$i] === $question['correct_answer'] ? '<span class="correct">Correct</span>' : '<span class="incorrect">Incorrect</span>'; ?>
                    </p>
                </div>
            <?php endforeach; ?>
            <div class="nav-buttons">
                <form method="POST" action="index.php?page=result">
                    <button type="submit" name="restart" class="begin-btn">Restart Quiz</button>
                </form>
                <?php if (!empty($result_data['incorrect_questions'])): ?>
                    <form method="POST" action="index.php?page=result">
                        <button type="submit" name="retake" class="retake-btn">Retake Incorrect Answers</button>
                    </form>
                <?php endif; ?>
            </div>
            <?php foreach ($errors as $error): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        <?php endif; ?>
        <div class="footer">
            © 2025 Kuru Exam
        </div>
    </div>
</body>
</html>