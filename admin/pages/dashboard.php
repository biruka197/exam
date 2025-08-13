<?php
$total_courses = $pdo->query("SELECT COUNT(DISTINCT course) FROM course")->fetchColumn();
$total_admins = $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
$total_errors = $pdo->query("SELECT COUNT(*) FROM error_report")->fetchColumn();

// Check if current admin has a credential registered
$stmt = $pdo->prepare("SELECT webauthn_credential_id FROM admin_users WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$has_credential = $stmt->fetchColumn();
?>
<div class="page-header"><h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1><p>Overview of the exam system.</p></div>
<div class="stats-grid">
    <div class="stat-card"><h3><?php echo $total_courses; ?></h3><p>Total Courses</p></div>
    <div class="stat-card"><h3><?php echo $total_admins; ?></h3><p>Admin Users</p></div>
    <div class="stat-card"><h3><?php echo $total_errors; ?></h3><p>Error Reports</p></div>
</div>

<div class="content-section">
    <div class="section-header"><i class="fas fa-fingerprint"></i> Fingerprint Authentication</div>
    <div class="section-content">
        <?php if ($has_credential): ?>
            <p>You have a fingerprint credential registered with this account.</p>
        <?php else: ?>
            <p>You have not registered a fingerprint for this account yet. Registering allows you to log in without a password.</p>
            <button id="webauthn-register-btn" class="btn btn-primary">Register This Device</button>
            <p id="webauthn-reg-status" style="margin-top: 1rem; color: #333;"></p>
        <?php endif; ?>
    </div>
</div>

<script>
    // --- Vanilla JavaScript for WebAuthn Registration ---
    const registerBtn = document.getElementById('webauthn-register-btn');
    if (registerBtn) {
        const regStatusElem = document.getElementById('webauthn-reg-status');

        // Re-declare helpers if not globally available, otherwise this is not needed
        function bufferToBase64url(buffer) {
            return btoa(String.fromCharCode.apply(null, new Uint8Array(buffer)))
                .replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
        }

        function base64urlToBuffer(base64url) {
            const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
            const raw = window.atob(base64);
            const buffer = new Uint8Array(raw.length);
            for (let i = 0; i < raw.length; i++) {
                buffer[i] = raw.charCodeAt(i);
            }
            return buffer;
        }

        registerBtn.addEventListener('click', async () => {
            regStatusElem.textContent = 'Requesting registration challenge...';
            try {
                // 1. Get challenge from server
                const challengeResp = await fetch('webauthn_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=get_registration_challenge'
                });
                const options = await challengeResp.json();
                if (options.error) throw new Error(options.error);

                // 2. Prepare options for browser API
                options.challenge = base64urlToBuffer(options.challenge);
                options.user.id = base64urlToBuffer(options.user.id);

                regStatusElem.textContent = 'Please use your fingerprint scanner to register...';
                
                // 3. Prompt user to create credential
                const credential = await navigator.credentials.create({ publicKey: options });

                // 4. Prepare credential for server verification
                const credentialForServer = {
                    id: credential.id,
                    rawId: bufferToBase64url(credential.rawId),
                    type: credential.type,
                    response: {
                        clientDataJSON: bufferToBase64url(credential.response.clientDataJSON),
                        attestationObject: bufferToBase64url(credential.response.attestationObject),
                    },
                };
                
                regStatusElem.textContent = 'Verifying registration...';

                // 5. Send to server
                const verifyResp = await fetch('webauthn_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'verify_registration', data: credentialForServer })
                });
                const verificationResult = await verifyResp.json();

                if (verificationResult.verified) {
                    regStatusElem.textContent = 'Success! Device registered. You can now use it to log in.';
                    registerBtn.style.display = 'none'; // Hide button after success
                } else {
                    throw new Error(verificationResult.error || 'Registration verification failed.');
                }
            } catch (err) {
                regStatusElem.textContent = 'Error: ' + err.message;
            }
        });
    }
</script>