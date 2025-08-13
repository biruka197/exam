<?php
// This file will now fetch its own data from the database.

// Include necessary files for database connection
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db_connect.php';

$db_error_message = null;
$reported_errors = [];
$fixed_errors = [];
$invalid_errors = [];

// The variable name is likely $conn but it holds a mysqli connection object.
if (isset($conn)) { 
    
    // --- UPDATED: Data fetching logic ---

    // 1. Create lookup maps from the `course` table.
    $exam_to_course_map = [];
    $exam_to_location_map = []; // Map exam_code to its file path

    // Fetch the course name, exam code, and the exam file location
    $courses_result = $conn->query("SELECT course, exam_code, exam FROM course"); 
    if ($courses_result) {
        while ($course_row = $courses_result->fetch_assoc()) {
            $exam_code = $course_row['exam_code'];
            // Map the exam_code to the course name
            $exam_to_course_map[$exam_code] = $course_row['course'];
            // Map the exam_code to its file path (location)
            $exam_to_location_map[$exam_code] = $course_row['exam'];
        }
        $courses_result->free_result();
    } else {
         $db_error_message = "Error fetching from 'course' table: " . $conn->error;
    }

    // 2. Fetch all error reports.
    $all_reports = [];
    $query = "SELECT * FROM error_report ORDER BY FIELD(status, 'reported', 'fixed', 'invalid'), id DESC";
    $result = $conn->query($query);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $all_reports[] = $row;
        }
        $result->free_result();

        // 3. Combine report data with course and question info.
        $reports_with_details = [];
        foreach ($all_reports as $report) {
            $exam_id = $report['exam_id'];
            $reported_question_number = $report['question_id']; // This is the question number
            
            // Look up course name from the map.
            $report['course_name'] = $exam_to_course_map[$exam_id] ?? 'Unknown Course';

            // --- MODIFIED: Find question by `question_number` instead of array index ---
            $question_text = 'Question text not found.'; // Default message
            $options = [];
            $explanation = 'No explanation available.';
            $correct_answer = 'N/A';
            $exam_location = $exam_to_location_map[$exam_id] ?? null;

            if ($exam_location) {
                // Construct the full server path from the project root.
                $file_path = __DIR__ . '/../' . $exam_location;
                
                if (file_exists($file_path)) {
                    $json_data = file_get_contents($file_path);
                    $exam_data = json_decode($json_data, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $question_text = 'Error decoding JSON from file: ' . $exam_location;
                    } elseif (!is_array($exam_data)) {
                        $question_text = "JSON root is not an array in file: " . $exam_location;
                    } else {
                        // Loop through questions to find a match by `question_number`.
                        $found_question = false;
                        foreach ($exam_data as $question_obj) {
                            if (isset($question_obj['question_number']) && $question_obj['question_number'] == $reported_question_number) {
                                // Success! Get the details from the matched question object.
                                $question_text = $question_obj['question'] ?? 'Question text key missing.';
                                $options = $question_obj['options'] ?? [];
                                $correct_answer = $question_obj['correct_answer'] ?? 'N/A';
                                $explanation = $question_obj['explanation'] ?? 'Explanation not available.';
                                $found_question = true;
                                break; // Stop searching once found
                            }
                        }
                        if (!$found_question) {
                             $question_text = "A question with number '{$reported_question_number}' was not found in the exam file.";
                        }
                    }

                } else {
                    $question_text = 'Exam file not found at the specified path: ' . $file_path;
                }
            } else {
                $question_text = 'Exam location not defined in the `course` table for this exam code: ' . $exam_id;
            }

            $report['question_text'] = $question_text;
            $report['question_number'] = $reported_question_number; // Use the number from the report
            $report['options'] = $options;
            $report['correct_answer'] = $correct_answer;
            $report['explanation'] = $explanation;
            // --- END MODIFICATION ---
            
            $reports_with_details[] = $report;
        }

        // 4. Filter reports into categories for display.
        $reported_errors = array_filter($reports_with_details, fn($report) => $report['status'] === 'reported');
        $fixed_errors = array_filter($reports_with_details, fn($report) => $report['status'] === 'fixed');
        $invalid_errors = array_filter($reports_with_details, fn($report) => $report['status'] === 'invalid');

    } else {
        $db_error_message = "Error fetching reports from the database: " . $conn->error;
    }
} else {
    $db_error_message = "Database connection failed. The connection object is not available.";
}

// Helper function to render a report card
function render_report_card($report, $type) {
    $status_colors = [
        'reported' => 'bg-yellow-500',
        'fixed' => 'bg-green-500',
        'invalid' => 'bg-gray-500',
    ];
    $status_color = $status_colors[$report['status']] ?? 'bg-gray-400';
    ?>
    <div class="report-card">
        <div class="card-header">
            <div>
                <div class="font-bold text-gray-800"><?php echo htmlspecialchars($report['course_name']); ?></div>
                <div class="text-sm text-gray-500">
                    Exam: <?php echo htmlspecialchars($report['exam_id']); ?> &bull; 
                    Question: #<?php echo htmlspecialchars($report['question_number']); ?>
                </div>
            </div>
            <span class="status-badge <?php echo $status_color; ?>"><?php echo htmlspecialchars($report['status']); ?></span>
        </div>
        <div class="card-body">
            <button class="show-question-btn" onclick="toggleQuestion('q-<?php echo $type; ?>-<?php echo $report['id']; ?>')">
                <i class="fas fa-eye mr-2"></i>Show Details
            </button>
        </div>
        <div id="q-<?php echo $type; ?>-<?php echo $report['id']; ?>" class="question-details">
            <p><strong>Question <?php echo htmlspecialchars($report['question_number']); ?>:</strong><br><?php echo nl2br(htmlspecialchars($report['question_text'])); ?></p>
            <div class="options">
                <strong>Options:</strong>
                <?php foreach($report['options'] as $key => $value): ?>
                    <p class="option-item"><?php echo htmlspecialchars($key); ?>) <?php echo htmlspecialchars($value); ?></p>
                <?php endforeach; ?>
            </div>
            <div class="correct-answer">
                <strong>Correct Answer:</strong> <?php echo htmlspecialchars($report['correct_answer']); ?>
            </div>
            <div class="explanation">
                <strong>Explanation:</strong>
                <p><?php echo nl2br(htmlspecialchars($report['explanation'])); ?></p>
            </div>
        </div>
    </div>
    <?php
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reported Questions - Kuru Exam</title>
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link rel="stylesheet" as="style" onload="this.rel='stylesheet'"
        href="https://fonts.googleapis.com/css2?display=swap&family=Inter%3Awght%40400%3B500%3B600%3B700%3B800%3B900&family=Noto+Sans%3Awght%40400%3B500%3B600%3B700%3B800%3B900">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Base styles */
        body { font-family: Inter, "Noto Sans", sans-serif; background-color: #f8f9fa; }
        .hero-gradient { background-color: #f8f9fa; }
        .cyber-grid {
            background-image: 
                linear-gradient(rgba(0,0,0,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,0,0,0.03) 1px, transparent 1px);
            background-size: 60px 60px;
        }
        .glass-effect {
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.8);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        .modern-button { background: #22c55e; color: white; font-weight: 600; }
        
        /* Main content styling */
        .page-header h1 { font-size: 2.25rem; }
        @media (min-width: 640px) {
            .page-header h1 { font-size: 3rem; }
        }

        /* Section styles */
        .content-section {
            background: white;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -1px rgba(0, 0, 0, 0.04);
        }
        .section-header {
            padding: 1.25rem 1.5rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: #1a202c;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .section-content { padding: 1rem; }
         @media (min-width: 640px) {
            .section-content { padding: 1.5rem; }
        }
        
        /* Card-based layout for reports */
        .report-card {
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            transition: box-shadow 0.2s;
        }
        .report-card:hover {
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
        }
        .card-body {
            padding: 0 1rem 1rem 1rem;
        }
        .show-question-btn {
            background-color: #4A5568;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            width: 100%;
            text-align: center;
        }
        .show-question-btn:hover { background-color: #2D3748; }
        
        .question-details {
            display: none;
            padding: 1rem;
            background-color: #f7fafc;
            border-top: 1px solid #e2e8f0;
            white-space: normal;
        }
        .question-details.visible { display: block; }

        .question-details p { margin-bottom: 0.75rem; }
        .question-details strong { color: #1a202c; }
        .question-details .options {
            margin-top: 1rem;
            margin-bottom: 1rem;
            padding-left: 1rem;
            border-left: 3px solid #cbd5e0;
        }
         .question-details .option-item { margin-left: 1rem; margin-bottom: 0.25rem; }
        .question-details .correct-answer {
             margin-top: 1.5rem;
             padding: 0.75rem;
             background-color: #e6f9f0;
             border-left: 3px solid #22c55e;
             font-weight: 500;
        }
        .question-details .explanation {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px dashed #e2e8f0;
            font-style: italic;
            color: #4a5568;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            border-radius: 9999px;
            color: white;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .error-box {
            background-color: #fee2e2;
            border: 1px solid #f87171;
            color: #b91c1c;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
        }
    </style>
</head>

<body>
    <div class="relative flex size-full min-h-screen flex-col hero-gradient group/design-root overflow-x-hidden cyber-grid">
        <div class="layout-container flex h-full grow flex-col relative z-10">
             <header class="flex items-center justify-between whitespace-nowrap px-4 sm:px-10 py-3 glass-effect sticky top-0 z-50 shadow-sm">
                <div class="flex items-center gap-4 sm:gap-8">
                    <a href="index.php" class="flex items-center gap-3 text-slate-900 hover:scale-105 transition-transform duration-300">
                        <div class="size-8 text-white modern-button rounded-lg p-1.5 shadow-md">
                            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z" fill="currentColor"></path>
                            </svg>
                        </div>
                        <h2 class="text-gray-800 text-xl font-bold tracking-tight">KURU EXAM</h2>
                    </a>
                </div>
                <a href="index.php" class="modern-button px-5 py-2 rounded-lg text-sm">
                    Back to Courses
                </a>
            </header>

            <main id="layout-content-container" class="flex justify-center flex-1 px-4 sm:px-10 py-12">
                <div class="w-full max-w-4xl">
                    <div class="page-header mb-10 text-center">
                        <h1 class="font-extrabold tracking-tight text-gray-800">Reported Questions</h1>
                  
                    </div>
                    
                    <?php if (isset($db_error_message)): ?>
                        <div class="error-box">
                            <strong>Error:</strong> <?php echo htmlspecialchars($db_error_message); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Section for Pending Reports -->
                    <div class="content-section">
                        <div class="section-header"><i class="fas fa-bug text-yellow-600"></i> Pending Review (<?php echo count($reported_errors); ?>)</div>
                        <div class="section-content">
                            <?php if (empty($reported_errors)): ?>
                                <p class="text-center text-gray-500 py-4">No pending reports. Great job!</p>
                            <?php else: ?>
                                <?php foreach ($reported_errors as $report) render_report_card($report, 'reported'); ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Section for Fixed Reports -->
                    <div class="content-section">
                        <div class="section-header"><i class="fas fa-check-circle text-green-600"></i> Fixed Reports (<?php echo count($fixed_errors); ?>)</div>
                        <div class="section-content">
                            <?php if (empty($fixed_errors)): ?>
                                <p class="text-center text-gray-500 py-4">No fixed reports yet.</p>
                            <?php else: ?>
                                <?php foreach ($fixed_errors as $report) render_report_card($report, 'fixed'); ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Section for Invalid Reports -->
                    <div class="content-section">
                        <div class="section-header"><i class="fas fa-minus-circle text-gray-500"></i> Invalid Reports (<?php echo count($invalid_errors); ?>)</div>
                        <div class="section-content">
                             <?php if (empty($invalid_errors)): ?>
                                <p class="text-center text-gray-500 py-4">No invalid reports.</p>
                            <?php else: ?>
                                <?php foreach ($invalid_errors as $report) render_report_card($report, 'invalid'); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script>
        function toggleQuestion(rowId) {
            const row = document.getElementById(rowId);
            const button = document.querySelector(`[onclick="toggleQuestion('${rowId}')"]`);
            if (row) {
                const isVisible = row.classList.toggle('visible');
                button.innerHTML = isVisible ? '<i class="fas fa-eye-slash mr-2"></i>Hide Details' : '<i class="fas fa-eye mr-2"></i>Show Details';
            }
        }
    </script>
</body>
</html>
