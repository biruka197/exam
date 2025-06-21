<?php
session_start();
require_once __DIR__ . '/../config.php'; // Include the configuration file
// Database configuration
$host = DB_HOST;
$dbname = DB_NAME;
$username = DB_USER;
$password = DB_PASS;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Function to generate unique course ID
function generateCourseId($course_name) {
    $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $course_name), 0, 3));
    if (strlen($prefix) < 3) {
        $prefix = str_pad($prefix, 3, 'X');
    }
    return $prefix . '_' . time();
}

// Function to generate unique exam code
function generateExamCode($course_name, $pdo) {
    $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $course_name), 0, 3));
    if (strlen($prefix) < 3) {
        $prefix = str_pad($prefix, 3, 'X');
    }
    
    $stmt = $pdo->prepare("SELECT exam_code FROM course WHERE exam_code LIKE ? ORDER BY exam_code DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last_code = $stmt->fetchColumn();
    
    if ($last_code) {
        $number = intval(substr($last_code, strlen($prefix))) + 1;
    } else {
        $number = 1;
    }
    
    return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
}

// Handle AJAX requests for question editing
if (isset($_POST['action']) && in_array($_POST['action'], ['get_question_for_edit', 'save_edited_question'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $project_root = __DIR__ . '/../';

    if ($action === 'get_question_for_edit') {
        $report_id = $_POST['report_id'];
        $stmt = $pdo->prepare("SELECT exam_id, question_id FROM error_report WHERE id = ?");
        $stmt->execute([$report_id]);
        $report = $stmt->fetch();

        if (!$report) {
            echo json_encode(['success' => false, 'error' => 'Report not found.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT exam FROM course WHERE exam_code = ?");
        $stmt->execute([$report['exam_id']]);
        $exam_file_relative_path = $stmt->fetchColumn();
        $exam_file_full_path = $project_root . $exam_file_relative_path;

        if (!$exam_file_relative_path || !file_exists($exam_file_full_path)) {
            echo json_encode(['success' => false, 'error' => 'Exam file not found.']);
            exit;
        }

        $questions = json_decode(file_get_contents($exam_file_full_path), true);
        $found_question = null;
        foreach ($questions as $question) {
            if ($question['question_number'] == $report['question_id']) {
                $found_question = $question;
                break;
            }
        }

        if ($found_question) {
            echo json_encode(['success' => true, 'question' => $found_question]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Question with ID ' . $report['question_id'] . ' not found in file.']);
        }
    }

    if ($action === 'save_edited_question') {
        $report_id = $_POST['report_id'];
        $question_data = $_POST['question_data'];

        $stmt = $pdo->prepare("SELECT exam_id, question_id FROM error_report WHERE id = ?");
        $stmt->execute([$report_id]);
        $report = $stmt->fetch();
        if (!$report) { echo json_encode(['success' => false, 'error' => 'Report not found.']); exit; }

        $stmt = $pdo->prepare("SELECT exam FROM course WHERE exam_code = ?");
        $stmt->execute([$report['exam_id']]);
        $exam_file_relative_path = $stmt->fetchColumn();
        $exam_file_full_path = $project_root . $exam_file_relative_path;

        if (!$exam_file_relative_path || !file_exists($exam_file_full_path)) { echo json_encode(['success' => false, 'error' => 'Exam file not found.']); exit; }

        $questions = json_decode(file_get_contents($exam_file_full_path), true);
        $question_index = -1;
        foreach ($questions as $index => $q) {
            if ($q['question_number'] == $report['question_id']) {
                $question_index = $index;
                break;
            }
        }

        if ($question_index !== -1) {
            $questions[$question_index]['question'] = $question_data['question'];
            $questions[$question_index]['options'] = $question_data['options'];
            $questions[$question_index]['correct_answer'] = $question_data['correct_answer'];
            $questions[$question_index]['explanation'] = $question_data['explanation'];
            
            file_put_contents($exam_file_full_path, json_encode($questions, JSON_PRETTY_PRINT));
            
            $stmt = $pdo->prepare("DELETE FROM error_report WHERE id = ?");
            $stmt->execute([$report_id]);

            echo json_encode(['success' => true, 'message' => 'Question updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Could not find question to update.']);
        }
    }

    exit;
}

// Get current page
$current_page = $_GET['page'] ?? 'dashboard';

// Handle Form POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_course':
            $course_name = $_POST['course'];
            $course_id = '';

            $stmt_check = $pdo->prepare("SELECT course_id FROM course WHERE course = ? LIMIT 1");
            $stmt_check->execute([$course_name]);
            $existing_course_id = $stmt_check->fetchColumn();

            if ($existing_course_id) {
                $course_id = $existing_course_id;
            } else {
                $course_id = generateCourseId($course_name);
            }

            $exam_code = generateExamCode($course_name, $pdo);
            $new_file_path = '';

            if (isset($_FILES['exam_file']) && $_FILES['exam_file']['error'] === UPLOAD_ERR_OK) {
                $file_tmp_path = $_FILES['exam_file']['tmp_name'];
                $file_name = basename($_FILES['exam_file']['name']);
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                if ($file_ext === 'json') {
                    $upload_dir = __DIR__ . '/exams/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                    $new_file_name = $exam_code . '.json';
                    $dest_path = $upload_dir . $new_file_name;

                    if (move_uploaded_file($file_tmp_path, $dest_path)) {
                        $new_file_path = 'admin/exams/' . $new_file_name;
                    } else {
                        $error_message = "Failed to move uploaded file.";
                    }
                } else {
                    $error_message = "Invalid file type. Only .json files are allowed.";
                }
            } else {
                $error_message = "File upload error or no file selected.";
            }

            if (empty($error_message) && !empty($new_file_path)) {
                $stmt = $pdo->prepare("INSERT INTO course (course, exam, course_id, exam_code) VALUES (?, ?, ?, ?)");
                $stmt->execute([$course_name, $new_file_path, $course_id, $exam_code]);
                $success_message = "Course added successfully! Course ID: $course_id, Exam Code: $exam_code";
            }
            break;

        case 'delete_course':
            $id = $_POST['id'];
            $stmt = $pdo->prepare("SELECT exam FROM course WHERE id = ?");
            $stmt->execute([$id]);
            $exam_file_to_delete = $stmt->fetchColumn();
            if ($exam_file_to_delete && file_exists(__DIR__ . '/../' . $exam_file_to_delete)) {
                 unlink(__DIR__ . '/../' . $exam_file_to_delete);
            }

            $stmt = $pdo->prepare("DELETE FROM course WHERE id = ?");
            $stmt->execute([$id]);
            $success_message = "Course deleted successfully!";
            break;
            
        case 'add_admin':
            $username = $_POST['username'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $error_message = "Username already exists!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
                $stmt->execute([$username, $password]);
                $success_message = "Admin user added successfully!";
            }
            break;
            
        case 'delete_admin':
            $id = $_POST['id'];
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users");
            $stmt->execute();
            if ($stmt->fetchColumn() > 1) {
                $stmt = $pdo->prepare("DELETE FROM admin_users WHERE id = ?");
                $stmt->execute([$id]);
                $success_message = "Admin user deleted successfully!";
            } else {
                $error_message = "Cannot delete the last admin user!";
            }
            break;
            
        case 'delete_error_report':
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM error_report WHERE id = ?");
            $stmt->execute([$id]);
            $success_message = "Error report deleted successfully!";
            break;
    }
}

// Fetch data for display
$courses_raw = $pdo->query("SELECT * FROM course ORDER BY course, exam_code")->fetchAll();
$grouped_courses = [];
foreach($courses_raw as $course) {
    $grouped_courses[$course['course']][] = $course;
}

$admin_users = $pdo->query("SELECT * FROM admin_users ORDER BY id")->fetchAll();
$error_reports = $pdo->query("SELECT * FROM error_report ORDER BY id DESC")->fetchAll();

$total_courses = $pdo->query("SELECT COUNT(DISTINCT course) FROM course")->fetchColumn();
$total_admins = $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
$total_errors = $pdo->query("SELECT COUNT(*) FROM error_report")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam System - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-start: #667eea;
            --primary-end: #764ba2;
            --primary-gradient: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; color: #333; }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: var(--primary-gradient); color: white; position: fixed; height: 100%; overflow-y: auto; z-index: 1000; transition: transform 0.3s ease; }
        .sidebar-header { padding: 2rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h2 { font-size: 1.3rem; margin-bottom: 0.5rem; }
        .sidebar-header p { font-size: 0.9rem; opacity: 0.8; }
        .sidebar-nav { padding: 1rem 0; }
        .nav-link { display: flex; align-items: center; padding: 1rem 1.5rem; color: white; text-decoration: none; transition: all 0.3s ease; }
        .nav-link:hover { background: rgba(255,255,255,0.1); padding-left: 2rem; }
        .nav-link.active { background: rgba(255,255,255,0.15); border-right: 4px solid white; }
        .nav-link i { margin-right: 1rem; width: 20px; text-align: center; }
        .sidebar-footer { position: absolute; bottom: 0; width: 100%; padding: 1rem 1.5rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .logout-btn { display: flex; align-items: center; width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.1); color: white; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; transition: background 0.3s ease; }
        .logout-btn:hover { background: rgba(255,255,255,0.2); }
        .main-content { flex: 1; margin-left: 280px; padding: 2rem; }
        .page-header { background: white; padding: 1.5rem 2rem; border-radius: 12px; box-shadow: 0 2px 20px rgba(0,0,0,0.08); margin-bottom: 2rem; }
        h1, h2, h3 { color: #2d3748; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 2px 20px rgba(0,0,0,0.08); position: relative; overflow: hidden; border-left: 4px solid var(--primary-start); }
        .stat-card h3 { font-size: 2.5rem; color: var(--primary-start); margin-bottom: 0.5rem; }
        .stat-card p { color: #718096; font-weight: 500; }
        .content-section { background: white; border-radius: 12px; box-shadow: 0 2px 20px rgba(0,0,0,0.08); margin-bottom: 2rem; overflow: hidden; }
        .section-header { background: var(--primary-gradient); color: white; padding: 1.5rem 2rem; font-size: 1.1rem; font-weight: 600; }
        .section-content { padding: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: 1rem; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem; transition: border-color 0.3s ease; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: var(--primary-start); box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .btn { padding: 1rem 1.5rem; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-primary { background: var(--primary-gradient); color: white; }
        .btn-danger { background: #e53e3e; color: white; }
        .btn-success { background: #38a169; color: white; }
        .table-container { overflow-x: auto; margin-top: 1.5rem; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 1rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .table th { background: #f7fafc; font-weight: 600; }
        .alert { padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid; }
        .alert-success { background: #f0fff4; color: #22543d; border-color: #38a169; }
        .alert-error { background: #fed7d7; color: #742a2a; border-color: #e53e3e; }
        .course-category h2 { font-size: 1.5rem; margin-top: 2rem; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0; }
        
        #drop-area { border: 2px dashed #ccc; border-radius: 8px; padding: 2rem; text-align: center; transition: all 0.3s ease; background-color: #f8f9fa; }
        #drop-area.highlight { border-color: var(--primary-start); background-color: #e9eafc; }
        #drop-area p { color: #666; }
        #file-input { display: none; }
        #file-name { margin-top: 1rem; font-weight: bold; color: var(--primary-start); }

        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); }
        .modal-content { background-color: white; margin: 5% auto; padding: 2rem; border-radius: 12px; width: 90%; max-width: 800px; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem; margin-bottom: 1rem; }
        .close-btn { font-size: 2rem; cursor: pointer; color: #718096; }

        .mobile-toggle { display: none; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 1rem; }
            .mobile-toggle { display: block; position: fixed; top: 1rem; left: 1rem; z-index: 1001; background: var(--primary-start); color: white; border: none; padding: 0.8rem; border-radius: 8px; }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <button class="mobile-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>

        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-graduation-cap"></i> Exam System</h2>
                <p>Admin Dashboard</p>
            </div>
            <nav class="sidebar-nav">
                <a href="?page=dashboard" class="nav-link <?php if($current_page == 'dashboard') echo 'active'; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="?page=courses" class="nav-link <?php if($current_page == 'courses') echo 'active'; ?>"><i class="fas fa-book"></i> Courses</a>
                <a href="?page=admins" class="nav-link <?php if($current_page == 'admins') echo 'active'; ?>"><i class="fas fa-users-cog"></i> Admin Users</a>
                <a href="?page=reports" class="nav-link <?php if($current_page == 'reports') echo 'active'; ?>"><i class="fas fa-exclamation-triangle"></i> Error Reports</a>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <main class="main-content">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if ($current_page === 'dashboard'): ?>
                <div class="page-header"><h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1><p>Overview of the exam system.</p></div>
                <div class="stats-grid">
                    <div class="stat-card"><h3><?php echo $total_courses; ?></h3><p>Total Courses</p></div>
                    <div class="stat-card"><h3><?php echo $total_admins; ?></h3><p>Admin Users</p></div>
                    <div class="stat-card"><h3><?php echo $total_errors; ?></h3><p>Error Reports</p></div>
                </div>
            <?php endif; ?>

            <?php if ($current_page === 'courses'): ?>
                <div class="page-header"><h1><i class="fas fa-book"></i> Course Management</h1><p>Add and manage exam courses.</p></div>
                <div class="content-section">
                    <div class="section-header"><i class="fas fa-plus"></i> Add New Course</div>
                    <div class="section-content">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add_course">
                            <div class="form-group">
                                <label for="course_name">Course Name:</label>
                                <input type="text" id="course_name" name="course" required>
                            </div>
                            <div class="form-group">
                                <label for="file-input">Exam File (JSON):</label>
                                <div id="drop-area">
                                    <p>Drag & drop your .json file here, or click to select file.</p>
                                    <p id="file-name"></p>
                                </div>
                                <input type="file" id="file-input" name="exam_file" accept=".json" required>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Course</button>
                        </form>
                    </div>
                </div>

                <div class="content-section">
                    <div class="section-header"><i class="fas fa-list"></i> Existing Courses</div>
                    <div class="section-content">
                        <?php foreach($grouped_courses as $course_name => $exams): ?>
                            <div class="course-category">
                                <h2><?php echo htmlspecialchars($course_name); ?></h2>
                                <div class="table-container">
                                    <table class="table">
                                        <thead><tr><th>Exam Code</th><th>File Path</th><th>Actions</th></tr></thead>
                                        <tbody>
                                        <?php foreach($exams as $exam): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($exam['exam_code']); ?></td>
                                                <td><?php echo htmlspecialchars($exam['exam']); ?></td>
                                                <td>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this exam? This will also delete the file.');">
                                                        <input type="hidden" name="action" value="delete_course">
                                                        <input type="hidden" name="id" value="<?php echo $exam['id']; ?>">
                                                        <button type="submit" class="btn btn-danger" style="padding: 0.5rem 1rem;"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php elseif ($current_page === 'reports'): ?>
                <div class="page-header"><h1><i class="fas fa-exclamation-triangle"></i> Error Reports</h1><p>View and manage reported question errors.</p></div>
                <div class="content-section">
                    <div class="section-header"><i class="fas fa-bug"></i> Reported Questions</div>
                    <div class="section-content">
                        <div class="table-container">
                            <table class="table">
                                <thead><tr><th>ID</th><th>Exam ID</th><th>Question ID</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <?php foreach ($error_reports as $report): ?>
                                    <tr id="report-row-<?php echo $report['id']; ?>">
                                        <td><?php echo $report['id']; ?></td>
                                        <td><?php echo htmlspecialchars($report['exam_id']); ?></td>
                                        <td><?php echo htmlspecialchars($report['question_id']); ?></td>
                                        <td>
                                            <button onclick="openEditModal(<?php echo $report['id']; ?>)" class="btn btn-success" style="padding: 0.5rem 1rem;"><i class="fas fa-edit"></i> Edit</button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?')">
                                                <input type="hidden" name="action" value="delete_error_report">
                                                <input type="hidden" name="id" value="<?php echo $report['id']; ?>">
                                                <button type="submit" class="btn btn-danger" style="padding: 0.5rem 1rem;"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <div id="edit-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Question</h2>
                <span class="close-btn" onclick="closeEditModal()">&times;</span>
            </div>
            <form id="edit-question-form">
                <input type="hidden" id="edit-report-id" name="report_id">
                <div class="form-group">
                    <label for="edit-question-text">Question Text</label>
                    <textarea id="edit-question-text" rows="3" class="form-group" style="width:100%; padding:1rem; border: 2px solid #e2e8f0; border-radius: 8px;"></textarea>
                </div>
                <div id="edit-options-container"></div>
                <div class="form-group">
                    <label for="edit-correct-answer">Correct Answer (Key)</label>
                    <input type="text" id="edit-correct-answer" class="form-group" style="width:100%; padding:1rem; border: 2px solid #e2e8f0; border-radius: 8px;">
                </div>
                <div class="form-group">
                    <label for="edit-explanation">Explanation</label>
                    <textarea id="edit-explanation" rows="3" class="form-group" style="width:100%; padding:1rem; border: 2px solid #e2e8f0; border-radius: 8px;"></textarea>
                </div>
                <button type="button" onclick="saveQuestion()" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('active'); }

        const dropArea = document.getElementById('drop-area'), fileInput = document.getElementById('file-input'), fileNameDisplay = document.getElementById('file-name');
        if (dropArea) {
            dropArea.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', () => { if (fileInput.files.length > 0) fileNameDisplay.textContent = `Selected: ${fileInput.files[0].name}`; });
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(e => dropArea.addEventListener(e, t => { t.preventDefault(); t.stopPropagation(); }, false));
            ['dragenter', 'dragover'].forEach(e => dropArea.addEventListener(e, () => dropArea.classList.add('highlight'), false));
            ['dragleave', 'drop'].forEach(e => dropArea.addEventListener(e, () => dropArea.classList.remove('highlight'), false));
            dropArea.addEventListener('drop', e => { fileInput.files = e.dataTransfer.files; fileNameDisplay.textContent = `Selected: ${fileInput.files[0].name}`; }, false);
        }

        const modal = document.getElementById('edit-modal');
        function openEditModal(reportId) {
            document.getElementById('edit-report-id').value = reportId;
            const formData = new FormData();
            formData.append('action', 'get_question_for_edit');
            formData.append('report_id', reportId);
            fetch('', { method: 'POST', body: formData }).then(res => res.json()).then(data => {
                if (data.success) {
                    const q = data.question;
                    document.getElementById('edit-question-text').value = q.question;
                    document.getElementById('edit-correct-answer').value = q.correct_answer;
                    document.getElementById('edit-explanation').value = q.explanation;
                    const optionsContainer = document.getElementById('edit-options-container');
                    optionsContainer.innerHTML = '';
                    for (const key in q.options) {
                        const optionGroup = document.createElement('div');
                        optionGroup.className = 'form-group';
                        optionGroup.innerHTML = `<label>Option ${key.toUpperCase()}</label><input type="text" class="edit-option-input" data-key="${key}" value="${q.options[key]}" style="width:100%; padding:1rem; border: 2px solid #e2e8f0; border-radius: 8px;">`;
                        optionsContainer.appendChild(optionGroup);
                    }
                    modal.style.display = 'block';
                } else { alert('Error: ' + data.error); }
            });
        }
        function closeEditModal() { modal.style.display = 'none'; }
        function saveQuestion() {
            const reportId = document.getElementById('edit-report-id').value, options = {};
            document.querySelectorAll('.edit-option-input').forEach(input => { options[input.dataset.key] = input.value; });
            const questionData = {
                question: document.getElementById('edit-question-text').value,
                options: options,
                correct_answer: document.getElementById('edit-correct-answer').value,
                explanation: document.getElementById('edit-explanation').value,
            };
            const formData = new FormData();
            formData.append('action', 'save_edited_question');
            formData.append('report_id', reportId);
            for(const key in questionData) {
                if(typeof questionData[key] === 'object') {
                    for (const subKey in questionData[key]) formData.append(`question_data[${key}][${subKey}]`, questionData[key][subKey]);
                } else { formData.append(`question_data[${key}]`, questionData[key]); }
            }
            fetch('', { method: 'POST', body: formData }).then(res => res.json()).then(data => {
                if (data.success) {
                    alert(data.message);
                    closeEditModal();
                    document.getElementById(`report-row-${reportId}`).remove();
                } else { alert('Error saving question: ' + data.error); }
            });
        }
        window.onclick = function(event) { if (event.target == modal) closeEditModal(); }
    </script>
</body>
</html>