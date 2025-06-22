<?php
// Fetch admin users from the database
$admin_users = $pdo->query("SELECT * FROM admin_users ORDER BY id")->fetchAll();
?>
<div class="page-header"><h1><i class="fas fa-users-cog"></i> Admin User Management</h1><p>Add and manage admin users.</p></div>

<div class="content-section">
    <div class="section-header"><i class="fas fa-user-plus"></i> Add New Admin</div>
    <div class="section-content">
        <form method="POST">
            <input type="hidden" name="action" value="add_admin">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Admin</button>
        </form>
    </div>
</div>

<div class="content-section">
    <div class="section-header"><i class="fas fa-users"></i> Existing Admins</div>
    <div class="section-content">
        <div class="table-container">
            <table class="table">
                <thead><tr><th>ID</th><th>Username</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach($admin_users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this admin user?');">
                                    <input type="hidden" name="action" value="delete_admin">
                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
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