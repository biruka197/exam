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
        case 'start_all_questions_exam':
            $course_name = $_POST['course'] ?? '';
            $response = ['success' => false, 'html' => '', 'error' => ''];

            if ($course_name) {
                // Order by exam_code to have a predictable order of question blocks
                $stmt = $conn->prepare("SELECT exam, course_id, exam_code FROM course WHERE course = ? ORDER BY exam_code ASC");
                $stmt->bind_param("s", $course_name);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $all_questions = [];
                    $course_id = null;
                    
                    while ($row = $result->fetch_assoc()) {
                        if (!$course_id) {
                            $course_id = $row['course_id'];
                        }
                        $exam_file_path = __DIR__ . '/../' . $row['exam'];
                        if (file_exists($exam_file_path) && is_readable($exam_file_path)) {
                            $file_content = file_get_contents($exam_file_path);
                            $decoded_json = json_decode($file_content, true);
                            $questions_from_file = isset($decoded_json['questions']) && is_array($decoded_json['questions']) ? $decoded_json['questions'] : $decoded_json;

                            if (is_array($questions_from_file) && !empty($questions_from_file)) {
                                // Shuffle questions within this specific exam file
                                shuffle($questions_from_file); 
                                // Then merge them into the main array
                                $all_questions = array_merge($all_questions, $questions_from_file);
                            }
                        }
                    }

                    if (!empty($all_questions)) {
                        // Shuffle options within each question
                        foreach ($all_questions as &$question) {
                            shuffleQuestionOptions($question);
                        }
                        unset($question);

                        $_SESSION['questions'] = $all_questions;
                        $_SESSION['num_questions'] = count($all_questions);
                        $_SESSION['exam_mode'] = 'all_questions_mode';
                        $_SESSION['exam_code'] = htmlspecialchars($course_name . ' - All-in-One');
                        $_SESSION['selected_course_id'] = $course_id;
                        $_SESSION['start_time'] = time();
                        $_SESSION['timer_duration'] = $_SESSION['num_questions'] * 60; // 1 minute per question
                        $_SESSION['answers'] = array_fill(0, $_SESSION['num_questions'], null);
                        $_SESSION['current_question'] = 0;
                        $_SESSION['show_answer'] = [];
                        $_SESSION['timer_on'] = true;
                        unset($_SESSION['paused_time']);

                        ob_start();
                        $question = $_SESSION['questions'][0];
                        $current_question_index = 0;
                        $current_exam_code = $_SESSION['exam_code'];
                        $remaining_time = $_SESSION['timer_duration'];
                        $timer_on = $_SESSION['timer_on'];
                        $show_answer = false;
                        $is_reported = false; 

                        include __DIR__ . '/../templates/quiz.php';
                        $response['html'] = ob_get_clean();
                        $response['success'] = true;
                    } else {
                        $response['error'] = "No questions found for this course.";
                    }

                } else {
                    $response['error'] = "No exams found for this course.";
                }
                $stmt->close();
            } else {
                $response['error'] = "Course name not provided.";
            }
            echo json_encode($response);
            exit;

        case 'submit_exam_now':
            $questions = $_SESSION['questions'] ?? [];
            $answers = $_SESSION['answers'] ?? [];
            $score = 0;
            $incorrect_questions = [];
            
            // Filter out questions that were not answered
            $answered_indices = array_keys(array_filter($answers, function($a) { return $a !== null; }));
            $total_answered = count($answered_indices);

            foreach ($answered_indices as $i) {
                if (isset($questions[$i])) {
                    $question = $questions[$i];
                     if (isset($answers[$i]) && $answers[$i] === $question['correct_answer']) {
                        $score++;
                    } else {
                        $incorrect_questions[] = $question;
                    }
                }
            }
            
            $_SESSION['incorrect_questions'] = $incorrect_questions;
            
            // Pass results to the template
            $total_for_score = $total_answered > 0 ? $total_answered : 1;
            
            ob_start();
            $is_unfinished_submission = true;
            $total = $total_answered; // Override total to be the number of questions answered
            include __DIR__ . '/../templates/results.php';
            $response['html'] = ob_get_clean();
            $response['success'] = true;
            echo json_encode($response);
            exit;

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
                        $exam_file_path = __DIR__ . '/../' . $row['exam']; 
                        if (!file_exists($exam_file_path) || !is_readable($exam_file_path)) {
                            $row['total_questions'] = 0;
                        } else {
                            $file_content = file_get_contents($exam_file_path);
                            $questions = json_decode($file_content, true);
                            if (isset($questions['questions']) && is_array($questions['questions'])) {
                                $row['total_questions'] = count($questions['questions']);
                            } else {
                                $row['total_questions'] = is_array($questions) ? count($questions) : 0;
                            }
                        }
                        $selected_exams[] = $row;
                    }
                    
                    // Inject the All-in-One exam if there is more than one exam
                    if (count($selected_exams) > 1) {
                        $total_all_questions = array_sum(array_column($selected_exams, 'total_questions'));

                        $all_in_one_exam = [
                            'exam_code' => 'ALL_IN_ONE',
                            'exam' => 'All questions combined',
                            'total_questions' => $total_all_questions,
                            'course' => $selected_course_name // Pass course name for the onclick handler
                        ];
                        // Add to the beginning of the array
                        array_unshift($selected_exams, $all_in_one_exam);
                    }

                    ob_start();
                    include __DIR__ . '/../templates/exam_list.php';
                    $response['html'] = ob_get_clean();
                    $response['success'] = true;
                } else {
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
                $stmt = $conn->prepare("SELECT course, course_id, exam, exam_code FROM course WHERE exam_code = ?");
                $stmt->bind_param("s", $exam_code);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    $exam_file_path = __DIR__ . '/../' . $row['exam'];
                    if (!file_exists($exam_file_path) || !is_readable($exam_file_path)) {
                        $response['error'] = "Exam file not found or is not accessible.";
                    } else {
                        $file_content = file_get_contents($exam_file_path);
                        $decoded_json = json_decode($file_content, true);
                        $questions = isset($decoded_json['questions']) && is_array($decoded_json['questions']) ? $decoded_json['questions'] : $decoded_json;

                        if (is_array($questions) && !empty($questions) && isset($questions[0]['lesson'])) {
                            // This is a study module, show lesson selection
                            $lessons = array_unique(array_column($questions, 'lesson'));
                            sort($lessons);
                            $module_name = $row['course'];
                            $module_code = $row['exam_code']; // Use exam_code as module_code
                            ob_start();
                            $temp_module_for_template = [
                                'module_name' => $module_name,
                                'module_code' => $module_code,
                                'lessons' => $lessons
                            ];
                            $module = $temp_module_for_template;
                            include __DIR__ . '/../templates/study_exam_list.php';
                            $response['html'] = ob_get_clean();
                            $response['success'] = true;
                        } else {
                            // This is a regular exam, show settings
                            $_SESSION['selected_course_id'] = $row['course_id'];
                            $_SESSION['exam_code'] = htmlspecialchars($row['exam_code']);
                            $course_name = $row['course'];
                            $loaded_exam_file_display = htmlspecialchars($row['exam']);
                            $total_questions_in_file = is_array($questions) ? count($questions) : 0;
                            ob_start();
                            include __DIR__ . '/../templates/exam_settings.php';
                            $response['html'] = ob_get_clean();
                            $response['success'] = true;
                        }
                    }
                } else {
                    $response['error'] = "Selected exam not found.";
                }
                $stmt->close();
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
                    $decoded_json = json_decode(file_get_contents($exam_file_path), true);

                    if (isset($decoded_json['questions']) && is_array($decoded_json['questions'])) {
                        $all_questions = $decoded_json['questions'];
                    } else {
                        $all_questions = $decoded_json;
                    }


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
        case 'proceed_to_study_exam':
            $module_code = $_POST['module_code'] ?? '';
            $lesson = $_POST['lesson'] ?? '';
            $response = ['success' => false, 'html' => '', 'error' => ''];

            if ($module_code && $lesson) {
                $stmt = $conn->prepare("SELECT * FROM study_modules WHERE module_code = ?");
                $stmt->bind_param("s", $module_code);
                $stmt->execute();
                $result = $stmt->get_result();
                $module = $result->fetch_assoc();
                $stmt->close();

                $file_path = '';
                if ($module) {
                    $file_path = $module['file_path'];
                } else {
                    $stmt = $conn->prepare("SELECT exam FROM course WHERE exam_code = ?");
                    $stmt->bind_param("s", $module_code);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $course = $result->fetch_assoc();
                    $stmt->close();
                    if ($course) {
                        $file_path = $course['exam'];
                    }
                }

                if ($file_path) {
                    $exam_file_path = __DIR__ . '/../' . $file_path;
                    if (file_exists($exam_file_path) && is_readable($exam_file_path)) {
                        $json_data = file_get_contents($exam_file_path);
                        $exam_data = json_decode($json_data, true);
                        $questions = isset($exam_data['questions']) && is_array($exam_data['questions']) ? $exam_data['questions'] : $exam_data;

                        if (is_array($questions)) {
                            $filtered_questions = array_filter($questions, function ($q) use ($lesson) {
                                return isset($q['lesson']) && $q['lesson'] == $lesson;
                            });
                            $total_questions_in_lesson = count(array_values($filtered_questions));

                            if ($total_questions_in_lesson > 0) {
                                $module_name = $module['module_name'] ?? $module_code;
                                $selected_lesson = $lesson;
                                $exam_file = $file_path;

                                ob_start();
                                include __DIR__ . '/../templates/study_exam_settings.php';
                                $response['html'] = ob_get_clean();
                                $response['success'] = true;
                            } else {
                                $response['error'] = "No questions found for the selected lesson.";
                            }
                        } else {
                            $response['error'] = "Invalid exam file format.";
                        }
                    } else {
                        $response['error'] = "Exam file not found at path: " . ($exam_file_path ?? 'undefined');
                    }
                } else {
                    $response['error'] = "Could not find a module or exam with code: $module_code";
                }
            } else {
                $response['error'] = "Module code or lesson not provided.";
            }
            echo json_encode($response);
            exit;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action specified']);
            exit;
    }
}