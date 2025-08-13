<?php
// Fetch all reports and separate them by status
$stmt = $pdo->query("SELECT * FROM error_report ORDER BY FIELD(status, 'reported', 'fixed', 'invalid'), id DESC");
$all_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Filter reports into categories
$reported_errors = array_filter($all_reports, function($report) {
    return $report['status'] === 'reported';
});

$fixed_errors = array_filter($all_reports, function($report) {
    return $report['status'] === 'fixed';
});

$invalid_errors = array_filter($all_reports, function($report) {
    return $report['status'] === 'invalid';
});
?>
<div class="page-header"><h1><i class="fas fa-exclamation-triangle"></i> Error Reports</h1><p>View and manage reported question errors.</p></div>

<!-- Section for Pending Reports -->
<div class="content-section">
    <div class="section-header"><i class="fas fa-bug"></i> Pending Review (<?php echo count($reported_errors); ?>)</div>
    <div class="section-content">
        <div class="table-container">
            <table class="table">
                <thead><tr><th>ID</th><th>Exam ID</th><th>Question ID</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($reported_errors)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 1rem;">No pending reports.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reported_errors as $report): ?>
                        <tr id="report-row-<?php echo $report['id']; ?>">
                            <td><?php echo $report['id']; ?></td>
                            <td><?php echo htmlspecialchars($report['exam_id']); ?></td>
                            <td><?php echo htmlspecialchars($report['question_id']); ?></td>
                            <td><span class="btn" style="background-color: #f6ad55; color: white; padding: 0.2rem 0.6rem; font-size: 0.8rem; border-radius: 12px; cursor: default;"><?php echo htmlspecialchars($report['status']); ?></span></td>
                            <td>
                                <button onclick="openEditModal(<?php echo $report['id']; ?>)" class="btn btn-success" style="padding: 0.5rem 1rem;"><i class="fas fa-edit"></i> Review & Fix</button>
                                <button onclick="markAsInvalid(<?php echo $report['id']; ?>)" class="btn btn-warning" style="padding: 0.5rem 1rem; background-color: #a0aec0; border-color: #a0aec0;"><i class="fas fa-times-circle"></i> Invalid</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this report permanently?');">
                                    <input type="hidden" name="action" value="delete_error_report">
                                    <input type="hidden" name="id" value="<?php echo $report['id']; ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 0.5rem 1rem;"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Section for Fixed Reports -->
<div class="content-section">
    <div class="section-header"><i class="fas fa-check-circle"></i> Fixed Reports (<?php echo count($fixed_errors); ?>)</div>
    <div class="section-content">
        <div class="table-container">
            <table class="table">
                <thead><tr><th>ID</th><th>Exam ID</th><th>Question ID</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($fixed_errors)): ?>
                         <tr><td colspan="5" style="text-align: center; padding: 1rem;">No fixed reports yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($fixed_errors as $report): ?>
                        <tr>
                            <td><?php echo $report['id']; ?></td>
                            <td><?php echo htmlspecialchars($report['exam_id']); ?></td>
                            <td><?php echo htmlspecialchars($report['question_id']); ?></td>
                            <td><span class="btn" style="background-color: #48bb78; color: white; padding: 0.2rem 0.6rem; font-size: 0.8rem; border-radius: 12px; cursor: default;"><?php echo htmlspecialchars($report['status']); ?></span></td>
                             <td>
                                <button onclick="openEditModal(<?php echo $report['id']; ?>, 'view')" class="btn btn-info" style="padding: 0.5rem 1rem;"><i class="fas fa-eye"></i> View</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this report permanently?');">
                                    <input type="hidden" name="action" value="delete_error_report">
                                    <input type="hidden" name="id" value="<?php echo $report['id']; ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 0.5rem 1rem;"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Section for Invalid Reports -->
<div class="content-section">
    <div class="section-header"><i class="fas fa-minus-circle"></i> Invalid Reports (<?php echo count($invalid_errors); ?>)</div>
    <div class="section-content">
        <div class="table-container">
            <table class="table">
                <thead><tr><th>ID</th><th>Exam ID</th><th>Question ID</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($invalid_errors)): ?>
                         <tr><td colspan="5" style="text-align: center; padding: 1rem;">No invalid reports.</td></tr>
                    <?php else: ?>
                        <?php foreach ($invalid_errors as $report): ?>
                        <tr>
                            <td><?php echo $report['id']; ?></td>
                            <td><?php echo htmlspecialchars($report['exam_id']); ?></td>
                            <td><?php echo htmlspecialchars($report['question_id']); ?></td>
                            <td><span class="btn" style="background-color: #a0aec0; color: white; padding: 0.2rem 0.6rem; font-size: 0.8rem; border-radius: 12px; cursor: default;"><?php echo htmlspecialchars($report['status']); ?></span></td>
                             <td>
                                <button onclick="openEditModal(<?php echo $report['id']; ?>, 'view')" class="btn btn-info" style="padding: 0.5rem 1rem;"><i class="fas fa-eye"></i> View</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this report permanently?');">
                                    <input type="hidden" name="action" value="delete_error_report">
                                    <input type="hidden" name="id" value="<?php echo $report['id']; ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 0.5rem 1rem;"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
