<?php
// Fetch error reports from the database
$error_reports = $pdo->query("SELECT * FROM error_report ORDER BY id DESC")->fetchAll();
?>
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
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this report?');">
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