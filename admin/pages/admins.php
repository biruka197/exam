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
                            <td class="action-buttons">
                                <button class="btn btn-secondary" onclick="registerFingerprint(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')"><i class="fas fa-fingerprint"></i> Register Fingerprint</button>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this admin user?');" style="display:inline;">
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

<script>
// Helper function to convert base64url to ArrayBuffer
function bufferDecode(value) {
    const s = atob(value.replace(/_/g, '/').replace(/-/g, '+'));
    const a = new Uint8Array(s.length);
    for (let i = 0; i < s.length; i++) {
        a[i] = s.charCodeAt(i);
    }
    return a;
}

// Helper function to convert ArrayBuffer to base64url
function bufferEncode(value) {
    return btoa(String.fromCharCode.apply(null, new Uint8Array(value)))
        .replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
}

async function registerFingerprint(userId, username) {
    try {
        // 1. Get challenge from server
        const response = await fetch('includes/ajax_handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=start_webauthn_registration&user_id=${userId}&username=${username}`
        });
        const creationOptions = await response.json();
        if (!creationOptions.success) throw new Error(creationOptions.error);

        // 2. Decode options from server
        creationOptions.data.challenge = bufferDecode(creationOptions.data.challenge);
        creationOptions.data.user.id = bufferDecode(creationOptions.data.user.id);
        if(creationOptions.data.excludeCredentials) {
            creationOptions.data.excludeCredentials.forEach(c => {
                c.id = bufferDecode(c.id);
            });
        }

        // 3. Prompt user for fingerprint
        const credential = await navigator.credentials.create({ publicKey: creationOptions.data });
        
        // 4. Send the new credential to server to save
        const attestationResponse = {
            id: credential.id,
            rawId: bufferEncode(credential.rawId),
            type: credential.type,
            response: {
                attestationObject: bufferEncode(credential.response.attestationObject),
                clientDataJSON: bufferEncode(credential.response.clientDataJSON),
            },
        };

        const verificationResponse = await fetch('includes/ajax_handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'finish_webauthn_registration',
                data: attestationResponse
            })
        });
        const verificationResult = await verificationResponse.json();

        if (verificationResult.success) {
            alert('Fingerprint registered successfully!');
        } else {
            throw new Error(verificationResult.error || 'Failed to register fingerprint.');
        }

    } catch (err) {
        console.error(err);
        alert('Error: ' + err.message);
    }
}
</script>