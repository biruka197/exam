<?php
// Fetch course data from the database
$courses_raw = $pdo->query("SELECT * FROM course ORDER BY course, exam_code")->fetchAll();
$grouped_courses = [];
foreach($courses_raw as $course) {
    $grouped_courses[$course['course']][] = $course;
}
?>
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