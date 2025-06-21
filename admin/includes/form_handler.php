<?php
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
                    $upload_dir = __DIR__ . '/../exams/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
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