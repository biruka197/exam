<?php
// This file contains all the logic for handling AJAX POST requests.

// Ensure utility functions are available, e.g., for shuffling options.
require_once __DIR__ . '/utils.php';

/**
 * Main router for all AJAX actions.
 * @param mysqli $conn The database connection object.
 */


function handle_ajax_request($conn)
{
    // Set the content type to JSON for all AJAX responses.
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if (empty($action)) {
        echo json_encode(['success' => false, 'error' => 'No action specified']);
        exit;
    }

    switch ($action) {

        case 'select_course':
            $course = $_POST['course'] ?? '';
            $exam_code = $_POST['exam_code'] ?? '';
            $response = ['success' => false, 'html' => '', 'error' => ''];

            if ($course || $exam_code) {
                $selected_course_name = '';
                $selected_exams = [];
                $stmt = $conn->prepare("SELECT course, course_id, exam, exam_code FROM course WHERE course = ? OR exam_code = ? ORDER BY exam_code");
                $stmt->bind_param("ss", $course, $exam_code);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        if (empty($selected_course_name)) {
                            $selected_course_name = $row['course'];
                        }
                        $exam_file_path = __DIR__ . '/../' . $row['exam']; // Assumes exam files are in the project root
                        if (!file_exists($exam_file_path) || !is_readable($exam_file_path)) {
                            error_log("Exam file not found or not readable: " . $exam_file_path);
                            $row['total_questions'] = 0;
                        } else {
                            $file_content = file_get_contents($exam_file_path);
                            if ($file_content === false) {
                                error_log("Failed to read exam file: " . $exam_file_path);
                                $row['total_questions'] = 0;
                            } else {
                                $questions = json_decode($file_content, true);
                                if ($questions === null || !is_array($questions)) {
                                    error_log("JSON parsing error or invalid format in file {$exam_file_path}");
                                    $row['total_questions'] = 0;
                                } else {
                                    $row['total_questions'] = count($questions);
                                }
                            }
                        }
                        $selected_exams[] = $row;
                    }

                    ob_start();
                    include __DIR__ . '/../templates/exam_list.php';
                    $response['html'] = ob_get_clean();
                    $response['success'] = true;
                } else {
                    error_log("No exams found for course: $course or exam_code: $exam_code");
                    $response['error'] = "No exams found for this course or exam code.";
                }
                $stmt->close();
            } else {
                $response['error'] = "Please select a course or enter an exam code.";
            }
            echo json_encode($response);
            exit;

        case 'proceed_to_exam':
            $exam_code = $_POST['exam_code'] ?? '';
            $response = ['success' => false, 'html' => '', 'error' => ''];

            if ($exam_code) {
                $stmt = $conn->prepare("SELECT course_id, exam, exam_code FROM course WHERE exam_code = ?");
                $stmt->bind_param("s", $exam_code);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    $_SESSION['selected_course_id'] = $row['course_id'];
                    $_SESSION['exam_code'] = htmlspecialchars($row['exam_code']);
                    $exam_file_path = __DIR__ . '/../' . $row['exam'];

                    $loaded_exam_file_display = htmlspecialchars($row['exam']);
                    $total_questions_in_file = 0;

                    if (file_exists($exam_file_path) && is_readable($exam_file_path)) {
                        $file_content = file_get_contents($exam_file_path);
                        if ($file_content !== false) {
                            $all_questions = json_decode($file_content, true);
                            if (is_array($all_questions)) {
                                $total_questions_in_file = count($all_questions);
                            } else {
                                $response['error'] = "Invalid exam file format.";
                            }
                        } else {
                            $response['error'] = "Failed to read exam file content.";
                        }
                    } else {
                        $response['error'] = "Exam file not found or is not accessible.";
                    }
                } else {
                    $response['error'] = "Selected exam not found.";
                }
                $stmt->close();

                if (empty($response['error'])) {
                    ob_start();
                    include __DIR__ . '/../templates/exam_settings.php';
                    $response['html'] = ob_get_clean();
                    $response['success'] = true;
                }
            } else {
                $response['error'] = "Please select an exam.";
            }
            echo json_encode($response);
            exit;

        case 'submit_settings':
            $response = ['success' => false, 'error' => ''];
            $exam_code = $_SESSION['exam_code'] ?? '';

            if (!$exam_code) {
                $response['error'] = "No exam selected. Please restart.";
                echo json_encode($response);
                exit;
            }

            $num_questions = (int) ($_POST['num_questions'] ?? 0);
            $range_start = (int) ($_POST['range_start'] ?? 1);
            $range_end = (int) ($_POST['range_end'] ?? 0);
            $order = $_POST['order'] ?? 'sequential';
            $exam_mode = $_POST['exam_mode'] ?? 'normal';

            $stmt = $conn->prepare("SELECT exam, course_id FROM course WHERE exam_code = ?");
            $stmt->bind_param("s", $exam_code);
            $stmt->execute();
            $result = $stmt->get_result();
            $course_data = $result->fetch_assoc();
            $stmt->close();

            if ($course_data) {
                $exam_file_path = __DIR__ . '/../' . $course_data['exam'];
                if (file_exists($exam_file_path) && is_readable($exam_file_path)) {
                    $all_questions = json_decode(file_get_contents($exam_file_path), true);

                    if (is_array($all_questions)) {
                        $ranged_questions = array_filter($all_questions, function ($q) use ($range_start, $range_end) {
                            return isset($q['question_number']) && $q['question_number'] >= $range_start && $q['question_number'] <= $range_end;
                        });
                        $filtered_questions = array_values($ranged_questions);

                        if ($order === 'random')
                            shuffle($filtered_questions);

                        $questions = array_slice($filtered_questions, 0, min($num_questions, count($filtered_questions)));

                        if ($exam_mode !== 'review') {
                            foreach ($questions as &$question)
                                shuffleQuestionOptions($question);
                            unset($question);
                        }

                        $_SESSION['questions'] = $questions;
                        $_SESSION['num_questions'] = count($questions);
                        $_SESSION['exam_mode'] = $exam_mode;
                        $_SESSION['selected_course_id'] = $course_data['course_id'];
                        $_SESSION['start_time'] = time();
                        $_SESSION['timer_duration'] = $_SESSION['num_questions'] * 60;
                        $_SESSION['answers'] = [];
                        $_SESSION['current_question'] = 0;
                        $_SESSION['show_answer'] = [];
                        $_SESSION['timer_on'] = true;
                        unset($_SESSION['paused_time']);

                        if (empty($questions)) {
                            $response['error'] = "No questions found for the selected settings.";
                        } else {
                            ob_start();
                            if ($exam_mode === 'review') {
                                include __DIR__ . '/../templates/quiz_review_scroll.php';
                            } else {
                                $question = $_SESSION['questions'][0];
                                $current_question_index = 0;
                                $current_exam_code = $_SESSION['exam_code'];
                                $remaining_time = $_SESSION['timer_duration'];
                                $timer_on = $_SESSION['timer_on'];
                                $show_answer = false;
                                $is_reported = false;
                                $report_check_stmt = $conn->prepare("SELECT id FROM error_report WHERE course_id = ? AND exam_id = ? AND question_id = ?");
                                $report_check_stmt->bind_param("ssi", $_SESSION['selected_course_id'], $_SESSION['exam_code'], $question['question_number']);
                                $report_check_stmt->execute();
                                if ($report_check_stmt->get_result()->num_rows > 0)
                                    $is_reported = true;
                                $report_check_stmt->close();
                                include __DIR__ . '/../templates/quiz.php';
                            }
                            $response['html'] = ob_get_clean();
                            $response['success'] = true;
                        }
                    } else {
                        $response['error'] = "Invalid exam file format.";
                    }
                } else {
                    $response['error'] = "Exam file not found.";
                }
            } else {
                $response['error'] = "Selected exam not found in database.";
            }

            echo json_encode($response);
            exit;

        case 'submit_answer':
        case 'navigate_to_question':
            // ... (rest of the file is unchanged)
            $response = ['success' => false, 'html' => '', 'error' => ''];

            if (!isset($_SESSION['questions']) || empty($_SESSION['questions'])) {
                $response['error'] = "No questions in session. Please restart the exam.";
                echo json_encode($response);
                exit;
            }

            $current_question_index = $_SESSION['current_question'] ?? 0;
            $navigate_to_index = (int) ($_POST['navigate_to'] ?? $current_question_index);

            if ($action === 'submit_answer') {
                $selected_option = $_POST['option'] ?? null;
                $_SESSION['answers'][$current_question_index] = $selected_option;
                $navigate_to_index = $current_question_index + 1;
            }

            if ($navigate_to_index >= $_SESSION['num_questions']) {
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
                $_SESSION['incorrect_questions'] = $incorrect_questions;

                ob_start();
                include __DIR__ . '/../templates/results.php';
                $response['html'] = ob_get_clean();
                $response['success'] = true;

            } else {
                $_SESSION['current_question'] = $navigate_to_index;
                $question = $_SESSION['questions'][$navigate_to_index];
                $current_question_index = $navigate_to_index;
                $is_reported = false;
                $report_check_stmt = $conn->prepare("SELECT id FROM error_report WHERE course_id = ? AND exam_id = ? AND question_id = ?");
                $report_check_stmt->bind_param("ssi", $_SESSION['selected_course_id'], $_SESSION['exam_code'], $question['question_number']);
                $report_check_stmt->execute();
                if ($report_check_stmt->get_result()->num_rows > 0) {
                    $is_reported = true;
                }
                $report_check_stmt->close();
                $current_exam_code = $_SESSION['exam_code'];
                $show_answer = $_SESSION['show_answer'][$navigate_to_index] ?? false;
                $timer_on = $_SESSION['timer_on'] ?? true;
                $timer_duration = $_SESSION['timer_duration'] ?? 0;
                $elapsed_time = time() - ($_SESSION['start_time'] ?? time());
                $remaining_time = max(0, $timer_duration - $elapsed_time);

                ob_start();
                include __DIR__ . '/../templates/quiz.php';
                $response['html'] = ob_get_clean();
                $response['success'] = true;
                $response['remaining_time'] = $remaining_time;
                $response['timer_on'] = $timer_on;
                $response['script'] = 'attachOptionClickListeners();';
            }
            echo json_encode($response);
            exit;

        case 'toggle_timer':
            $response = ['success' => false, 'timer_on' => $_SESSION['timer_on'] ?? true];
            $current_remaining_time = (int) ($_POST['remaining_time'] ?? 0);

            if (isset($_SESSION['timer_on'])) {
                if ($_SESSION['timer_on']) {
                    $_SESSION['timer_on'] = false;
                    $_SESSION['paused_time'] = $current_remaining_time;
                } else {
                    $_SESSION['timer_on'] = true;
                    $paused_time = $_SESSION['paused_time'] ?? $_SESSION['timer_duration'] ?? 0;
                    $_SESSION['start_time'] = time() - ($_SESSION['timer_duration'] - max(0, $paused_time));
                    unset($_SESSION['paused_time']);
                }
            } else {
                $_SESSION['timer_on'] = false;
                $_SESSION['paused_time'] = $current_remaining_time;
            }

            $response['success'] = true;
            $response['timer_on'] = $_SESSION['timer_on'];
            echo json_encode($response);
            exit;

        case 'toggle_answer':
            $current_question_index = $_SESSION['current_question'] ?? 0;
            $_SESSION['show_answer'][$current_question_index] = !($_SESSION['show_answer'][$current_question_index] ?? false);
            $response = ['success' => true, 'show_answer' => $_SESSION['show_answer'][$current_question_index]];
            echo json_encode($response);
            exit;

        case 'exit_exam':
        case 'restart_quiz':
            session_destroy();
            $response = ['success' => true, 'redirect' => 'index.php'];
            echo json_encode($response);
            exit;

        case 'retake_incorrect':
            $response = ['success' => false, 'error' => ''];
            $incorrect_questions = $_SESSION['incorrect_questions'] ?? [];

            if (!empty($incorrect_questions)) {
                shuffle($incorrect_questions);
                foreach ($incorrect_questions as &$question) {
                    shuffleQuestionOptions($question);
                }
                unset($question);

                $_SESSION['questions'] = $incorrect_questions;
                $_SESSION['num_questions'] = count($incorrect_questions);
                $_SESSION['start_time'] = time();
                $_SESSION['timer_duration'] = $_SESSION['num_questions'] * 60;
                $_SESSION['answers'] = [];
                $_SESSION['current_question'] = 0;
                $_SESSION['show_answer'] = [];
                $_SESSION['timer_on'] = true;
                unset($_SESSION['paused_time']);
                unset($_SESSION['incorrect_questions']);

                $question = $_SESSION['questions'][0];
                $current_question_index = 0;
                $current_exam_code = $_SESSION['exam_code'];
                $remaining_time = $_SESSION['timer_duration'];
                $timer_on = true;
                $show_answer = false;

                ob_start();
                include __DIR__ . '/../templates/quiz.php';
                $response['html'] = ob_get_clean();
                $response['success'] = true;
                $response['remaining_time'] = $remaining_time;
                $response['timer_on'] = $timer_on;
                $response['script'] = 'attachOptionClickListeners();';
            } else {
                $response['error'] = "No incorrect questions to retake.";
            }
            echo json_encode($response);
            exit;
        case 'report_question':
            $response = ['success' => false, 'error' => '', 'status' => ''];
            $question_number = (int) ($_POST['question_number'] ?? 0);
            $exam_code = $_SESSION['exam_code'] ?? '';
            $course_id = $_SESSION['selected_course_id'] ?? '';

            if ($question_number > 0 && !empty($exam_code) && !empty($course_id)) {
                $check_stmt = $conn->prepare("SELECT id FROM error_report WHERE course_id = ? AND exam_id = ? AND question_id = ?");
                $check_stmt->bind_param("ssi", $course_id, $exam_code, $question_number);
                $check_stmt->execute();
                $result = $check_stmt->get_result();

                if ($result->num_rows > 0) {
                    $response['success'] = true;
                    $response['status'] = 'already_reported';
                } else {
                    $insert_stmt = $conn->prepare("INSERT INTO error_report (course_id, exam_id, question_id) VALUES (?, ?, ?)");
                    if ($insert_stmt) {
                        $insert_stmt->bind_param("ssi", $course_id, $exam_code, $question_number);
                        if ($insert_stmt->execute()) {
                            $response['success'] = true;
                            $response['status'] = 'reported';
                        } else {
                            $response['error'] = 'Database error: Could not save the report.';
                        }
                        $insert_stmt->close();
                    } else {
                        $response['error'] = 'Database error: Could not prepare the statement.';
                    }
                }
                $check_stmt->close();
            } else {
                $response['error'] = 'Missing required information to submit a report.';
            }
            echo json_encode($response);
            exit;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action specified']);
            exit;
    }
}