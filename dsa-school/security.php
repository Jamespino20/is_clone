<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/api/helpers.php';
require_once __DIR__ . '/api/data_structures.php';

$email = $_SESSION['user_email'] ?? null;
if (!$email) {
    header('Location: index.php');
    exit;
}

$user = get_user_by_email($email);
if (!$user) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$message = '';
$messageType = '';

// Header data for nav and notifications
$dsManager = DataStructuresManager::getInstance();
$userRole = get_role_display_name($user['role']);
$userNotifications = array_filter($dsManager->getNotificationQueue()->getAll(), fn($n) => $n['user_email'] === $email);
$unreadNotifications = array_filter($userNotifications, fn($n) => !$n['read']);

// Handle 2FA toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'enable_2fa') {
        if (empty($user['totp_secret'])) {
            $newSecret = gen_totp_secret(16);
            if (update_user($email, ['totp_secret' => $newSecret])) {
                $user = get_user_by_email($email);
                $message = '2FA has been enabled. Please scan the QR code with your authenticator app.';
                $messageType = 'success';
            } else {
                $message = 'Failed to enable 2FA.';
                $messageType = 'error';
            }
        }
    } elseif ($action === 'disable_2fa') {
        $code = $_POST['code'] ?? '';
        if ($code && totp_verify($user['totp_secret'], $code)) {
            if (update_user($email, ['totp_secret' => ''])) {
                $user = get_user_by_email($email);
                $message = '2FA has been disabled successfully.';
                $messageType = 'success';
            } else {
                $message = 'Failed to disable 2FA.';
                $messageType = 'error';
            }
        } else {
            $message = 'Invalid 2FA code.';
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Security Settings - St. Luke's School</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="../assets/js/toast.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
</head>
<body>
<?php $subtitle = 'Security Settings'; $assetPrefix = ''; include __DIR__ . '/partials/header.php'; ?>

    <main class="container">
        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <section class="card">
            <h2>Two-Factor Authentication</h2>
            
            <?php if (!empty($user['totp_secret'])): ?>
                <!-- 2FA is enabled -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="alert alert-success">
                            <h4>✅ 2FA is Enabled</h4>
                            <p>Your account is protected with two-factor authentication.</p>
                        </div>
                        
                        <h4>Disable 2FA</h4>
                        <p class="text-muted">To disable 2FA, enter your current authenticator code:</p>
                        <form method="POST">
                            <input type="hidden" name="action" value="disable_2fa">
                            <div class="mb-3">
                                <label for="disable_code" class="form-label">Authenticator Code</label>
                                <input type="text" class="form-control" id="disable_code" name="code" 
                                       placeholder="Enter 6-digit code" maxlength="6" required>
                            </div>
                            <button type="submit" class="btn btn-danger">Disable 2FA</button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <h4>Security Information</h4>
                        <ul class="list-unstyled">
                            <li>✅ Account protected with 2FA</li>
                            <li>✅ Login requires authenticator code</li>
                            <li>✅ Enhanced security against unauthorized access</li>
                        </ul>
                        
                        <div class="alert alert-info">
                            <strong>Tip:</strong> Keep your authenticator app secure and don't share your device with others.
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- 2FA is disabled -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="alert alert-warning">
                            <h4>⚠️ 2FA is Disabled</h4>
                            <p>Enable two-factor authentication for enhanced security.</p>
                        </div>
                        
                        <h4>Enable 2FA</h4>
                        <p class="text-muted">Scan the QR code with your authenticator app (Google Authenticator, Authy, etc.):</p>
                        
                        <?php if (!empty($user['totp_secret'])): ?>
                            <div class="qr-section">
                                <div id="qrcode"></div>
                                <div class="secret-display mt-3">
                                    <strong>Manual Entry Code:</strong><br>
                                    <code class="secret-text"><?= htmlspecialchars($user['totp_secret']) ?></code>
                                    <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copySecret()">Copy</button>
                                </div>
                                <div class="instructions mt-3">
                                    <small class="text-muted">
                                        Scan the QR code with your authenticator app or manually enter the code above.
                                    </small>
                                </div>
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="enable_2fa">
                                <button type="submit" class="btn btn-success">Enable 2FA</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h4>Benefits of 2FA</h4>
                        <ul class="list-unstyled">
                            <li>🔒 Extra layer of security</li>
                            <li>🛡️ Protection against password theft</li>
                            <li>📱 Works with popular authenticator apps</li>
                            <li>⚡ Quick and easy to use</li>
                        </ul>
                        
                        <div class="alert alert-info">
                            <strong>Recommended Apps:</strong><br>
                            • Google Authenticator<br>
                            • Authy<br>
                            • Microsoft Authenticator
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2>Security Recommendations</h2>
            <div class="row">
                <div class="col-md-6">
                    <h4>Password Security</h4>
                    <ul>
                        <li>Use a strong, unique password</li>
                        <li>Don't reuse passwords from other sites</li>
                        <li>Consider using a password manager</li>
                        <li>Change your password regularly</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h4>Account Security</h4>
                    <ul>
                        <li>Enable two-factor authentication</li>
                        <li>Log out from shared computers</li>
                        <li>Keep your contact information updated</li>
                        <li>Report suspicious activity immediately</li>
                    </ul>
                </div>
            </div>
        </section>
    </main>

    <!-- Dark Mode Toggle -->
    <div class="dark-mode-toggle" onclick="toggleDarkMode()">
        <span id="darkModeIcon">🌙</span>
    </div>

    <script>
        // Dark mode persistence + toggle
        document.addEventListener('DOMContentLoaded', function() {
            const darkMode = localStorage.getItem('darkMode');
            if (darkMode === 'true') {
                document.body.classList.add('dark-mode');
                document.getElementById('darkModeIcon').textContent = '☀️';
            }
        });
        function toggleDarkMode() {
            const body = document.body;
            const icon = document.getElementById('darkModeIcon');
            if (body.classList.contains('dark-mode')) {
                body.classList.remove('dark-mode');
                icon.textContent = '🌙';
                localStorage.setItem('darkMode', 'false');
            } else {
                body.classList.add('dark-mode');
                icon.textContent = '☀️';
                localStorage.setItem('darkMode', 'true');
            }
        }

        <?php if (!empty($user['totp_secret'])): ?>
        // Generate QR code for 2FA setup
        function generateQRCode() {
            const secret = '<?= $user['totp_secret'] ?>';
            const email = '<?= $user['email'] ?>';
            const issuer = 'St. Luke\'s School of San Rafael';
            
            const qrData = `otpauth://totp/${encodeURIComponent(issuer)}:${encodeURIComponent(email)}?secret=${secret}&issuer=${encodeURIComponent(issuer)}`;
            
            const qrContainer = document.getElementById('qrcode');
            if (qrContainer && typeof qrcode !== 'undefined') {
                try {
                    qrContainer.innerHTML = '';
                    const qr = qrcode(0, 'M');
                    qr.addData(qrData);
                    qr.make();
                    
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    const size = 200;
                    const moduleCount = qr.getModuleCount();
                    const cellSize = Math.floor(size / moduleCount);
                    const actualSize = cellSize * moduleCount;
                    
                    canvas.width = actualSize;
                    canvas.height = actualSize;
                    
                    for (let row = 0; row < moduleCount; row++) {
                        for (let col = 0; col < moduleCount; col++) {
                            ctx.fillStyle = qr.isDark(row, col) ? '#000000' : '#FFFFFF';
                            ctx.fillRect(col * cellSize, row * cellSize, cellSize, cellSize);
                        }
                    }
                    
                    qrContainer.appendChild(canvas);
                } catch (error) {
                    console.error('QR Code generation error:', error);
                    qrContainer.innerHTML = '<div class="alert alert-warning">QR code generation failed. Please use the manual entry code.</div>';
                }
            }
        }
        
        function copySecret() {
            const secret = '<?= $user['totp_secret'] ?>';
            navigator.clipboard.writeText(secret).then(() => {
                showSuccess('Secret code copied to clipboard!');
            });
        }
        
        // Generate QR code when page loads
        document.addEventListener('DOMContentLoaded', generateQRCode);
        <?php endif; ?>
    </script>
</body>
</html>
