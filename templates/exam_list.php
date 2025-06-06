<?php
// templates/exam_list.php
?>
<div class="exam-list">
    <h3>Available Exams for <?php echo htmlspecialchars($selected_course_name); ?></h3>
    <ul>
        <?php foreach ($selected_exams as $exam): ?>
            <li>
                <span><?php echo htmlspecialchars($exam['exam_code']); ?> (<?php echo htmlspecialchars(basename($exam['exam'], '.json')); ?>) (<?php echo $exam['total_questions']; ?> Questions)</span>
                <button onclick="proceedToExam('<?php echo htmlspecialchars($exam['exam_code']); ?>')">Select Exam</button>
            </li>
        <?php endforeach; ?>
    </ul>
</div>