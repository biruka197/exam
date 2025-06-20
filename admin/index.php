<?php
session_start();

// Database configuration
$host = '127.0.0.1';
$dbname = 'exam';
$username = 'root';
$password = '';

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
    
    // Find next available number
    $stmt = $pdo->prepare("SELECT exam_code FROM course WHERE exam_code LIKE ? ORDER BY exam_code DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last_code = $stmt->fetchColumn();
    
    if ($last_code) {
        $number = intval(substr($last_code, -3)) + 1;
    } else {
        $number = 1;
    }
    
    return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
}

// Get current page
$current_page = $_GET['page'] ?? 'dashboard';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_course':
            $course = $_POST['course'];
            $exam = $_POST['exam'];
            $course_id = generateCourseId($course);
            $exam_code = generateExamCode($course, $pdo);
            
            $stmt = $pdo->prepare("INSERT INTO course (course, exam, course_id, exam_code) VALUES (?, ?, ?, ?)");
            $stmt->execute([$course, $exam, $course_id, $exam_code]);
            $success_message = "Course added successfully! Course ID: $course_id, Exam Code: $exam_code";
            break;
            
        case 'edit_course':
            $id = $_POST['id'];
            $course = $_POST['course'];
            $exam = $_POST['exam'];
            
            $stmt = $pdo->prepare("UPDATE course SET course = ?, exam = ? WHERE id = ?");
            $stmt->execute([$course, $exam, $id]);
            $success_message = "Course updated successfully!";
            break;
            
        case 'delete_course':
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM course WHERE id = ?");
            $stmt->execute([$id]);
            $success_message = "Course deleted successfully!";
            break;
            
        case 'add_admin':
            $username = $_POST['username'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            // Check if username already exists
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
            // Don't delete if it's the only admin
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users");
            $stmt->execute();
            $admin_count = $stmt->fetchColumn();
            
            if ($admin_count > 1) {
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

// Fetch data based on current page
$courses = [];
$admin_users = [];
$error_reports = [];
$edit_course = null;

if ($current_page === 'courses' || $current_page === 'dashboard') {
    $courses = $pdo->query("SELECT * FROM course ORDER BY id DESC")->fetchAll();
}

if ($current_page === 'admins' || $current_page === 'dashboard') {
    $admin_users = $pdo->query("SELECT * FROM admin_users ORDER BY id")->fetchAll();
}

if ($current_page === 'reports' || $current_page === 'dashboard') {
    $error_reports = $pdo->query("SELECT * FROM error_report ORDER BY id DESC")->fetchAll();
}

// Handle course editing
if ($current_page === 'courses' && isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM course WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_course = $stmt->fetch();
}

// Get statistics
$total_courses = $pdo->query("SELECT COUNT(*) FROM course")->fetchColumn();
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }

        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h2 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }

        .sidebar-header p {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0.5rem 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            padding-left: 2rem;
        }

        .nav-link.active {
            background: rgba(255,255,255,0.15);
            border-right: 4px solid white;
        }

        .nav-link i {
            margin-right: 1rem;
            width: 20px;
            text-align: center;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 0.8rem;
            background: rgba(255,255,255,0.1);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.2);
        }

        .logout-btn i {
            margin-right: 0.5rem;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }

        .page-header {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }

        .page-header h1 {
            color: #2d3748;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #718096;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stat-card h3 {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .stat-card p {
            color: #718096;
            font-weight: 500;
        }

        .stat-card i {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            font-size: 2rem;
            color: #667eea;
            opacity: 0.3;
        }

        /* Content Sections */
        .content-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .section-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .section-content {
            padding: 2rem;
        }

        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2d3748;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Buttons */
        .btn {
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-danger {
            background: #e53e3e;
            color: white;
        }

        .btn-danger:hover {
            background: #c53030;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #38a169;
            color: white;
        }

        .btn-success:hover {
            background: #2f855a;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #718096;
            color: white;
        }

        .btn-secondary:hover {
            background: #4a5568;
            transform: translateY(-2px);
        }

        /* Tables */
        .table-container {
            overflow-x: auto;
            margin-top: 1.5rem;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .table th {
            background: #f7fafc;
            font-weight: 600;
            color: #2d3748;
        }

        .table tr:hover {
            background: #f7fafc;
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }

        .alert-success {
            background: #f0fff4;
            color: #22543d;
            border-color: #38a169;
        }

        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border-color: #e53e3e;
        }

        /* Mobile Responsive */
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: #667eea;
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: 8px;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .mobile-toggle {
                display: block;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Question Editor Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .close {
            font-size: 2rem;
            cursor: pointer;
            color: #718096;
        }

        .close:hover {
            color: #2d3748;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Mobile Toggle Button -->
        <button class="mobile-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-graduation-cap"></i> Exam System</h2>
                <p>Admin Dashboard</p>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a href="?page=dashboard" class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </div>
                <div class="nav-item">
                    <a href="?page=courses" class="nav-link <?php echo $current_page === 'courses' ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i>
                        Courses
                    </a>
                </div>
                <div class="nav-item">
                    <a href="?page=admins" class="nav-link <?php echo $current_page === 'admins' ? 'active' : ''; ?>">
                        <i class="fas fa-users-cog"></i>
                        Admin Users
                    </a>
                </div>
                <div class="nav-item">
                    <a href="?page=reports" class="nav-link <?php echo $current_page === 'reports' ? 'active' : ''; ?>">
                        <i class="fas fa-exclamation-triangle"></i>
                        Error Reports
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($current_page === 'dashboard'): ?>
                <!-- Dashboard Page -->
                <div class="page-header">
                    <h1><i class="fas fa-tachometer-alt"></i> Dashboard Overview</h1>
                    <p>Welcome to the Exam System Admin Dashboard</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fas fa-book"></i>
                        <h3><?php echo $total_courses; ?></h3>
                        <p>Total Courses</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-users-cog"></i>
                        <h3><?php echo $total_admins; ?></h3>
                        <p>Admin Users</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3><?php echo $total_errors; ?></h3>
                        <p>Error Reports</p>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="content-section">
                    <div class="section-header">
                        <i class="fas fa-clock"></i> Recent Activity
                    </div>
                    <div class="section-content">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Recent Courses</th>
                                        <th>Exam Code</th>
                                        <th>Course ID</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($courses, 0, 5) as $course): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($course['course']); ?></td>
                                        <td><span class="badge"><?php echo htmlspecialchars($course['exam_code']); ?></span></td>
                                        <td><?php echo htmlspecialchars($course['course_id']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php elseif ($current_page === 'courses'): ?>
                <!-- Courses Page -->
                <div class="page-header">
                    <h1><i class="fas fa-book"></i> Course Management</h1>
                    <p>Manage your exam courses and settings</p>
                </div>

                <div class="content-section">
                    <div class="section-header">
                        <i class="fas fa-plus"></i> <?php echo $edit_course ? 'Edit Course' : 'Add New Course'; ?>
                    </div>
                    <div class="section-content">
                        <form method="POST">
                            <input type="hidden" name="action" value="<?php echo $edit_course ? 'edit_course' : 'add_course'; ?>">
                            <?php if ($edit_course): ?>
                                <input type="hidden" name="id" value="<?php echo $edit_course['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Course Name:</label>
                                    <input type="text" name="course" required 
                                           value="<?php echo $edit_course ? htmlspecialchars($edit_course['course']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Exam File Path:</label>
                                    <input type="text" name="exam" placeholder="e.g., exams/exam1.json" required
                                           value="<?php echo $edit_course ? htmlspecialchars($edit_course['exam']) : ''; ?>">
                                </div>
                            </div>
                            
                            <?php if ($edit_course): ?>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>Course ID (Auto-generated):</label>
                                        <input type="text" value="<?php echo htmlspecialchars($edit_course['course_id']); ?>" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>Exam Code (Auto-generated):</label>
                                        <input type="text" value="<?php echo htmlspecialchars($edit_course['exam_code']); ?>" disabled>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p style="color: #718096; margin-bottom: 1rem;">
                                    <i class="fas fa-info-circle"></i>
                                    Course ID and Exam Code will be automatically generated based on the course name.
                                </p>
                            <?php endif; ?>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                <?php echo $edit_course ? 'Update Course' : 'Add Course'; ?>
                            </button>
                            
                            <?php if ($edit_course): ?>
                                <a href="?page=courses" class="btn btn-secondary">
                                    <i class="fas fa-times"></i>
                                    Cancel
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <div class="content-section">
                    <div class="section-header">
                        <i class="fas fa-list"></i> Existing Courses
                    </div>
                    <div class="section-content">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Course</th>
                                        <th>Exam File</th>
                                        <th>Course ID</th>
                                        <th>Exam Code</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td><?php echo $course['id']; ?></td>
                                        <td><?php echo htmlspecialchars($course['course']); ?></td>
                                        <td><?php echo htmlspecialchars($course['exam']); ?></td>
                                        <td><span class="badge"><?php echo htmlspecialchars($course['course_id']); ?></span></td>
                                        <td><span class="badge"><?php echo htmlspecialchars($course['exam_code']); ?></span></td>
                                        <td>
                                            <a href="?page=courses&edit=<?php echo $course['id']; ?>" class="btn btn-success" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_course">
                                                <input type="hidden" name="id" value="<?php echo $course['id']; ?>">
                                                <button type="submit" class="btn btn-danger" style="padding: 0.5rem 1rem; font-size: 0.9rem;" onclick="return confirm('Are you sure?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php elseif ($current_page === 'admins'): ?>
                <!-- Admin Users Page -->
                <div class="page-header">
                    <h1><i class="fas fa-users-cog"></i> Admin User Management</h1>
                    <p>Manage administrator accounts</p>
                </div>

                <div class="content-section">
                    <div class="section-header">
                        <i class="fas fa-user-plus"></i> Add New Admin User
                    </div>
                    <div class="section-content">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_admin">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Username:</label>
                                    <input type="text" name="username" required>
                                </div>
                                <div class="form-group">
                                    <label>Password:</label>
                                    <input type="password" name="password" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i>
                                Add Admin User
                            </button>
                        </form>
                    </div>
                </div>

                <div class="content-section">
                    <div class="section-header">
                        <i class="fas fa-users"></i> Existing Admin Users
                    </div>
                    <div class="section-content">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($admin_users as $admin): ?>
                                    <tr>
                                        <td><?php echo $admin['id']; ?></td>
                                        <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_admin">
                                                <input type="hidden" name="id" value="<?php echo $admin['id']; ?>">
                                                <button type="submit" class="btn btn-danger" style="padding: 0.5rem 1rem; font-size: 0.9rem;" onclick="return confirm('Are you sure?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php elseif ($current_page === 'reports'): ?>
                <!-- Error Reports Page -->
                <div class="page-header">
                    <h1><i class="fas fa-exclamation-triangle"></i> Error Reports</h1>
                    <p>View and manage reported errors</p>
                </div>

                <div class="content-section">
                    <div class="section-header">
                        <i class="fas fa-bug"></i> Error Reports List
                    </div>
                    <div class="section-content">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Message</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($error_reports as $report): ?>
                                    <tr>
                                        <td><?php echo $report['id']; ?></td>
                                        <td><?php echo htmlspecialchars($report['user']); ?></td>
                                        <td><?php echo htmlspecialchars($report['message']); ?></td>
                                        <td><?php echo htmlspecialchars($report['created_at']); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_error_report">
                                                <input type="hidden" name="id" value="<?php echo $report['id']; ?>">
                                                <button type="submit" class="btn btn-danger" style="padding: 0.5rem 1rem; font-size: 0.9rem;" onclick="return confirm('Are you sure?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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
        </div>
    </div>
    <script>
        function toggleSidebar() {
            var sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }
    </script>
</body>
</html>