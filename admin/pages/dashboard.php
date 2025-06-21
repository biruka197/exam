<?php
$total_courses = $pdo->query("SELECT COUNT(DISTINCT course) FROM course")->fetchColumn();
$total_admins = $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
$total_errors = $pdo->query("SELECT COUNT(*) FROM error_report")->fetchColumn();
?>
<div class="page-header"><h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1><p>Overview of the exam system.</p></div>
<div class="stats-grid">
    <div class="stat-card"><h3><?php echo $total_courses; ?></h3><p>Total Courses</p></div>
    <div class="stat-card"><h3><?php echo $total_admins; ?></h3><p>Admin Users</p></div>
    <div class="stat-card"><h3><?php echo $total_errors; ?></h3><p>Error Reports</p></div>
</div>