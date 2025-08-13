<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Generate a unique module code
    function generateModuleCode($name, $pdo)
    {
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 3));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM study_modules WHERE module_code LIKE ?");
        $stmt->execute([$prefix . '%']);
        $count = $stmt->fetchColumn();
        return $prefix . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }

    switch ($action) {
        // --- START: STUDY MODULES CRUD ---
        case 'add_study_module':
            $module_name = $_POST['module_name'] ?? '';
            if (empty($module_name) || !isset($_FILES['exam_file']) || $_FILES['exam_file']['error'] !== UPLOAD_ERR_OK) {
                $error_message = "Module name and a valid JSON file are required.";
                break;
            }

            $file_info = pathinfo($_FILES['exam_file']['name']);
            if (strtolower($file_info['extension']) !== 'json') {
                $error_message = "Invalid file type. Only .json files are allowed.";
                break;
            }

            try {
                $module_code = generateModuleCode($module_name, $pdo);
                $upload_dir = __DIR__ . '/../exams/study_modules/';
                if (!is_dir($upload_dir))
                    mkdir($upload_dir, 0755, true);

                $new_filename = $module_code . '.json';
                $dest_path = $upload_dir . $new_filename;
                $db_path = 'admin/exams/study_modules/' . $new_filename;

                if (move_uploaded_file($_FILES['exam_file']['tmp_name'], $dest_path)) {
                    $stmt = $pdo->prepare("INSERT INTO study_modules (module_name, module_code, file_path) VALUES (?, ?, ?)");
                    $stmt->execute([$module_name, $module_code, $db_path]);
                    $success_message = "Study module '" . htmlspecialchars($module_name) . "' added successfully!";
                } else {
                    $error_message = "Failed to move uploaded file.";
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
            break;

        case 'update_study_module':
            $module_id = $_POST['id'] ?? '';
            $module_name = $_POST['module_name'] ?? '';
            if (empty($module_id) || empty($module_name)) {
                $error_message = "Module ID and name are required for an update.";
                break;
            }

            try {
                // Update the name first
                $stmt = $pdo->prepare("UPDATE study_modules SET module_name = ? WHERE id = ?");
                $stmt->execute([$module_name, $module_id]);
                $success_message = "Module name updated successfully.";

                // Check if a new file was uploaded
                if (isset($_FILES['exam_file']) && $_FILES['exam_file']['error'] === UPLOAD_ERR_OK) {
                    // Fetch old file path to delete it
                    $stmt = $pdo->prepare("SELECT file_path FROM study_modules WHERE id = ?");
                    $stmt->execute([$module_id]);
                    $old_file_path_rel = $stmt->fetchColumn();
                    if ($old_file_path_rel) {
                        $old_file_path_abs = __DIR__ . '/../' . $old_file_path_rel;
                        if (file_exists($old_file_path_abs)) {
                            unlink($old_file_path_abs);
                        }
                    }

                    // Move the new file
                    $upload_dir = dirname(__DIR__ . '/../' . $old_file_path_rel) . '/';
                    $new_filename = basename($old_file_path_rel);
                    $dest_path = $upload_dir . $new_filename;
                    if (move_uploaded_file($_FILES['exam_file']['tmp_name'], $dest_path)) {
                        $success_message .= " File replaced successfully.";
                    } else {
                        $error_message = "Failed to replace the file.";
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Database error during update: " . $e->getMessage();
            }
            break;

        case 'delete_study_module':
            $module_id = $_POST['id'] ?? '';
            if (empty($module_id)) {
                $error_message = "Module ID is required for deletion.";
                break;
            }

            try {
                // Get file path before deleting DB record
                $stmt = $pdo->prepare("SELECT file_path FROM study_modules WHERE id = ?");
                $stmt->execute([$module_id]);
                $file_path_rel = $stmt->fetchColumn();

                // Delete the database record
                $delete_stmt = $pdo->prepare("DELETE FROM study_modules WHERE id = ?");
                $delete_stmt->execute([$module_id]);

                // If DB deletion was successful, delete the file
                if ($delete_stmt->rowCount() > 0 && $file_path_rel) {
                    $file_path_abs = __DIR__ . '/../' . $file_path_rel;
                    if (file_exists($file_path_abs)) {
                        unlink($file_path_abs);
                    }
                }
                $success_message = "Study module deleted successfully.";

            } catch (PDOException $e) {
                $error_message = "Database error during deletion: " . $e->getMessage();
            }
            break;
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

            if (isset($_FILES['exam_file']) && is_array($_FILES['exam_file']['name'])) {
                $file_count = count($_FILES['exam_file']['name']);
                $success_count = 0;
                $error_messages_arr = [];

                for ($i = 0; $i < $file_count; $i++) {
                    if ($_FILES['exam_file']['error'][$i] === UPLOAD_ERR_OK) {
                        $file_tmp_path = $_FILES['exam_file']['tmp_name'][$i];
                        $file_name = basename($_FILES['exam_file']['name'][$i]);
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                        if ($file_ext === 'json') {
                            $exam_code = generateExamCode($course_name, $pdo);
                            $upload_dir = __DIR__ . '/../exams/';
                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0755, true);
                            }
                            $new_file_name = $exam_code . '.json';
                            $dest_path = $upload_dir . $new_file_name;

                            if (move_uploaded_file($file_tmp_path, $dest_path)) {
                                $new_file_path = 'admin/exams/' . $new_file_name;
                                $stmt = $pdo->prepare("INSERT INTO course (course, exam, course_id, exam_code) VALUES (?, ?, ?, ?)");
                                $stmt->execute([$course_name, $new_file_path, $course_id, $exam_code]);
                                $success_count++;
                            } else {
                                $error_messages_arr[] = "Failed to move uploaded file: " . htmlspecialchars($file_name);
                            }
                        } else {
                            $error_messages_arr[] = "Invalid file type for " . htmlspecialchars($file_name) . ". Only .json files are allowed.";
                        }
                    } elseif ($_FILES['exam_file']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                        $error_messages_arr[] = "File upload error for " . htmlspecialchars($_FILES['exam_file']['name'][$i]) . ". Code: " . $_FILES['exam_file']['error'][$i];
                    }
                }

                if ($success_count > 0) {
                    $success_message = "$success_count exam(s) added successfully for course '" . htmlspecialchars($course_name) . "'!";
                }
                if (!empty($error_messages_arr)) {
                    $error_message = implode("<br>", $error_messages_arr);
                }

            } else {
                $error_message = "No files were selected for upload.";
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