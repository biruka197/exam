<?php
// Generates a unique course ID
function generateCourseId($course_name) {
    $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $course_name), 0, 3));
    if (strlen($prefix) < 3) {
        $prefix = str_pad($prefix, 3, 'X');
    }
    return $prefix . '_' . time();
}

// Generates a unique exam code
function generateExamCode($course_name, $pdo) {
    $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $course_name), 0, 3));
    if (strlen($prefix) < 3) {
        $prefix = str_pad($prefix, 3, 'X');
    }
    
    $stmt = $pdo->prepare("SELECT exam_code FROM course WHERE exam_code LIKE ? ORDER BY exam_code DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last_code = $stmt->fetchColumn();
    
    $number = $last_code ? intval(substr($last_code, strlen($prefix))) + 1 : 1;
    
    return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
}
?>