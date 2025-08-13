<?php
// Fetch online users from the database who were active in the last 5 minutes
$stmt = $pdo->query("SELECT * FROM online_users WHERE last_seen > NOW() - INTERVAL 5 MINUTE");
$online_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="page-header">
    <h1><i class="fas fa-users"></i> Online Users</h1>
    <p>View users currently active on the platform.</p>
</div>

<div class="content-section">
    <div class="section-header"><i class="fas fa-list"></i> Active Users</div>
    <div class="section-content">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>IP Address</th>
                        <th>Browser</th>
                        <th>Course ID</th>
                        <th>Last Seen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($online_users)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 1rem;">No users are currently online.</td></tr>
                    <?php else: ?>
                        <?php foreach ($online_users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($user['ip_address']); ?></td>
                            <td><?php echo htmlspecialchars($user['browser_data']); ?></td>
                            <td><?php echo htmlspecialchars($user['course_id'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($user['last_seen']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function fetchOnlineUsers() {
    fetch('includes/ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_online_users'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const tbody = document.querySelector('.table tbody');
            tbody.innerHTML = '';
            if (data.users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 1rem;">No users are currently online.</td></tr>';
            } else {
                data.users.forEach(user => {
                    const row = `
                        <tr>
                            <td>${escapeHTML(user.user_id)}</td>
                            <td>${escapeHTML(user.ip_address)}</td>
                            <td>${escapeHTML(user.browser_data)}</td>
                            <td>${escapeHTML(user.course_id || 'N/A')}</td>
                            <td>${escapeHTML(user.last_seen)}</td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }
        }
    })
    .catch(error => console.error('Error fetching online users:', error));
}

function escapeHTML(str) {
    if (typeof str !== 'string') {
        return '';
    }
    return str.replace(/[&<>"']/g, function(match) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[match];
    });
}

setInterval(fetchOnlineUsers, 5000); // Refresh every 5 seconds
</script>