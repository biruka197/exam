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
                    // This block generates the HTML fragment for the exam list.
                    ?>
                    <div class="exam-list">
                        <h3>Available Exams for <?php echo htmlspecialchars($selected_course_name); ?></h3>
                        <ul>
                            <?php foreach ($selected_exams as $exam): ?>
                                <div class="exam-list">
                                  
                                    <ul class="exam-list-ul">
                                      
                                            <li class="exam-simple-item">
                                                <strong><?php echo htmlspecialchars($exam['exam_code']); ?></strong> -
                                                <?php echo htmlspecialchars(basename($exam['exam'], '.json')); ?>:
                                                <?php echo $exam['total_questions']; ?> Questions
                                                <button class="exam-select-btn"
                                                    onclick="proceedToExam('<?php echo htmlspecialchars($exam['exam_code']); ?>')">
                                                    Select Exam
                                                </button>
                                            </li>
                                      
                                    </ul>
                                </div>
                                <style>
                                    .exam-list {
                                        max-width: 500px;
                                        margin: 2rem auto;
                                        background: #f8fafc;
                                        border-radius: 12px;
                                        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.07);
                                        padding: 2rem 1.5rem;
                                    }

                                    .exam-list h3 {
                                        text-align: center;
                                        color: #374151;
                                        margin-bottom: 1.5rem;
                                        font-size: 1.3rem;
                                        font-weight: 600;
                                    }

                                    .exam-list-ul {
                                        padding: 0;
                                        margin: 0;
                                        list-style: none;
                                    }

                                    .exam-simple-item {
                                        display: flex;
                                        align-items: center;
                                        justify-content: space-between;
                                        background: #fff;
                                        border-radius: 8px;
                                        margin-bottom: 1rem;
                                        padding: 1rem 1.2rem;
                                        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.06);
                                        font-size: 1rem;
                                    }

                                    .exam-simple-item strong {
                                        color: #4f46e5;
                                        margin-right: 0.5rem;
                                    }

                                    .exam-select-btn {
                                        background:green;
                                        color: #fff;
                                        border: none;
                                        border-radius: 20px;
                                        padding: 0.5rem 1.2rem;
                                        font-size: 1rem;
                                        font-weight: 500;
                                        cursor: pointer;
                                        transition: background 0.2s, transform 0.2s;
                                    }

                                    .exam-select-btn:hover {
                                        background: linear-gradient(90deg, #818cf8 0%, #6366f1 100%);
                                        transform: scale(1.05);
                                    }

                                    @media (max-width: 600px) {
                                        .exam-list {
                                            padding: 1rem 0.5rem;
                                        }

                                        .exam-simple-item {
                                            flex-direction: column;
                                            align-items: flex-start;
                                            gap: 0.5rem;
                                            font-size: 0.98rem;
                                        }

                                        .exam-select-btn {
                                            width: 100%;
                                        }
                                    }
                                </style>

                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php
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

                    // Variables needed by the template
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
            } else {
                $num_questions = (int) ($_POST['num_questions'] ?? 0);
                $range_start = (int) ($_POST['range_start'] ?? 1);
                $range_end = (int) ($_POST['range_end'] ?? 0);
                $order = $_POST['order'] ?? 'random';

                $stmt = $conn->prepare("SELECT exam FROM course WHERE exam_code = ?");
                $stmt->bind_param("s", $exam_code);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    $exam_file_path = __DIR__ . '/../' . $row['exam'];
                    if (file_exists($exam_file_path) && is_readable($exam_file_path)) {
                        $file_content = file_get_contents($exam_file_path);
                        $all_questions = json_decode($file_content, true);

                        if (is_array($all_questions)) {
                            $total_questions_in_file = count($all_questions);
                            if ($range_start < 1 || $range_end > $total_questions_in_file || $range_start > $range_end) {
                                $response['error'] = "Invalid question range.";
                            } else {
                                // 1. Filter by range
                                $ranged_questions = array_filter($all_questions, function ($q) use ($range_start, $range_end) {
                                    return isset($q['question_number']) && is_numeric($q['question_number']) && $q['question_number'] >= $range_start && $q['question_number'] <= $range_end;
                                });
                                // 2. Ensure uniqueness and re-index
                                $unique_questions = [];
                                $seen_question_numbers = [];
                                foreach ($ranged_questions as $q) {
                                    if (!isset($seen_question_numbers[$q['question_number']])) {
                                        $unique_questions[] = $q;
                                        $seen_question_numbers[$q['question_number']] = true;
                                    }
                                }
                                $filtered_questions = array_values($unique_questions);
                                // 3. Shuffle if random
                                if ($order === 'random') {
                                    shuffle($filtered_questions);
                                }
                                // 4. Slice to the desired number
                                $questions = array_slice($filtered_questions, 0, min($num_questions, count($filtered_questions)));
                                // 5. Shuffle options for each question
                                foreach ($questions as &$question) {
                                    shuffleQuestionOptions($question);
                                }
                                unset($question);

                                // Store in session and set up exam state
                                $_SESSION['questions'] = $questions;
                                $_SESSION['num_questions'] = count($questions);
                                $_SESSION['start_time'] = time();
                                $_SESSION['timer_duration'] = $_SESSION['num_questions'] * 60;
                                $_SESSION['answers'] = [];
                                $_SESSION['current_question'] = 0;
                                $_SESSION['show_answer'] = [];
                                $_SESSION['timer_on'] = true;
                                unset($_SESSION['paused_time']);

                                if (!empty($_SESSION['questions'])) {
                                    // Variables needed by the quiz.php template
                                    $question = $_SESSION['questions'][0];
                                    $current_question_index = 0;
                                    $current_exam_code = $_SESSION['exam_code'];
                                    $remaining_time = $_SESSION['timer_duration'];
                                    $timer_on = $_SESSION['timer_on'];
                                    $show_answer = false; // Never show answer on first load

                                    ob_start();
                                    include __DIR__ . '/../templates/quiz.php';
                                    $response['html'] = ob_get_clean();
                                    $response['success'] = true;
                                    $response['remaining_time'] = $remaining_time;
                                    $response['timer_on'] = $timer_on;
                                    $response['script'] = 'attachOptionClickListeners();';
                                } else {
                                    $response['error'] = "No questions found for the selected settings.";
                                }
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
                $stmt->close();
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
                // End of exam - Calculate results
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
                // Navigate to the next question
                $_SESSION['current_question'] = $navigate_to_index;

                // Variables needed by the quiz.php template
                $question = $_SESSION['questions'][$navigate_to_index];
                $current_question_index = $navigate_to_index;
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

                // Reset session for the retake
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

                // Variables needed for the quiz template
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

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action specified']);
            exit;
    }
}