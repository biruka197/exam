<?php
// This page is the new admin interface for managing study modules.

// Fetch all existing study modules from the new table
$stmt = $pdo->prepare("SELECT * FROM study_modules ORDER BY module_name");
$stmt->execute();
$study_modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <h1><i class="fas fa-book-reader"></i> Manage Study Modules</h1>
    <p>Upload new study modules, edit existing ones, or delete them.</p>
</div>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
<?php endif; ?>
<?php if (isset($error_message)): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div>
<?php endif; ?>


<div class="content-section">
    <div class="section-header"><i class="fas fa-plus"></i> Add New Study Module</div>
    <div class="section-content">
        <form action="" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_study_module">
            <div class="form-group">
                <label for="module_name">Module Name</label>
                <input type="text" name="module_name" id="module_name" class="form-group" style="width:100%; padding:1rem; border: 2px solid #e2e8f0; border-radius: 8px;" placeholder="e.g., Engine Fundamentals" required>
            </div>
            <div class="form-group">
                <label for="exam_file">Select JSON File</label>
                <input type="file" name="exam_file" id="exam_file" class="form-control" accept=".json" required>
                <small class="text-muted">Upload a JSON file with the detailed question structure.</small>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload Module</button>
        </form>
    </div>
</div>

<div class="content-section">
    <div class="section-header"><i class="fas fa-list"></i> Existing Study Modules</div>
    <div class="section-content">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Module Name</th>
                        <th>Module Code</th>
                        <th>File Path</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($study_modules)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 1rem;">No study modules found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($study_modules as $module): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($module['module_name']); ?></td>
                                <td><?php echo htmlspecialchars($module['module_code']); ?></td>
                                <td><?php echo htmlspecialchars($module['file_path']); ?></td>
                                <td class="actions">
                                    <button class="btn btn-primary" style="padding: 0.5rem 1rem;" onclick='openEditModuleModal(<?php echo json_encode($module); ?>)'><i class="fas fa-edit"></i> Edit</button>
                                    <form action="" method="post" onsubmit="return confirm('Are you sure? This will delete the file and database entry.');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_study_module">
                                        <input type="hidden" name="id" value="<?php echo $module['id']; ?>">
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

<div id="edit-module-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Study Module</h2>
            <span class="close-btn" onclick="closeEditModuleModal()">&times;</span>
        </div>
        <form action="" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_study_module">
            <input type="hidden" id="edit_module_id" name="id">
            <div class="form-group">
                <label for="edit_module_name">Module Name</label>
                <input type="text" id="edit_module_name" name="module_name" class="form-group" style="width:100%; padding:1rem; border: 2px solid #e2e8f0; border-radius: 8px;" required>
            </div>
            <div class="form-group">
                <label for="edit_exam_file">Upload New JSON File (Optional)</label>
                <input type="file" id="edit_exam_file" name="exam_file" class="form-control" accept=".json">
                <small class="text-muted">If you upload a new file, it will replace the old one.</small>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
        </form>
    </div>
</div>

<script>
function openEditModuleModal(module) {
    document.getElementById('edit_module_id').value = module.id;
    document.getElementById('edit_module_name').value = module.module_name;
    document.getElementById('edit-module-modal').style.display = 'block';
}

function closeEditModuleModal() {
    document.getElementById('edit-module-modal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target == document.getElementById('edit-module-modal')) {
        closeEditModuleModal();
    }
}
</script>