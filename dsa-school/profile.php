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

// Header/nav data
$dsManager = DataStructuresManager::getInstance();
$userRole = get_role_display_name($user['role']);
$userNotifications = array_filter($dsManager->getNotificationQueue()->getAll(), fn($n) => $n['user_email'] === $email);
$unreadNotifications = array_filter($userNotifications, fn($n) => !$n['read']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if ($name && $newEmail) {
        // Check if email is being changed and if it's already taken
        if ($newEmail !== $email) {
            if (get_user_by_email($newEmail)) {
                $message = 'Email address is already in use.';
                $messageType = 'error';
            } else {
                // Update user information
                $updateData = [
                    'name' => $name,
                    'email' => $newEmail
                ];
                
                if ($newPassword && $newPassword === $confirmPassword) {
                    if (verify_password($currentPassword, $user['password'])) {
                        $updateData['password'] = hash_password($newPassword);
                    } else {
                        $message = 'Current password is incorrect.';
                        $messageType = 'error';
                    }
                }
                
                if (update_user($email, $updateData)) {
                    $_SESSION['user_email'] = $newEmail;
                    $user = get_user_by_email($newEmail);
                    $message = 'Profile updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to update profile.';
                    $messageType = 'error';
                }
            }
        } else {
            // Just updating name
            if (update_user($email, ['name' => $name])) {
                $user = get_user_by_email($email);
                $message = 'Profile updated successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to update profile.';
                $messageType = 'error';
            }
        }
    } else {
        $message = 'Please fill in all required fields.';
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Profile - St. Luke's School</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <header class="topbar">
        <div class="topbar-left">
            <img src="assets/img/school-logo.png" alt="School Logo" class="topbar-logo">
            <div class="topbar-title">
                <h1>St. Luke's School of San Rafael</h1>
                <span class="topbar-subtitle">Profile Management</span>
            </div>
        </div>
        <div class="topbar-right">
            <div class="user-info">
                <span class="user-name">Welcome, <?= htmlspecialchars($user['name']) ?></span>
                <span class="user-role"><?= $userRole ?></span>
            </div>
            <nav>
                <a href="profile.php" class="nav-link">Profile</a>
                <a href="security.php" class="nav-link">Security</a>
                <a href="notifications.php" class="nav-link">
                  🔔 Notifications
                  <?php if (count($unreadNotifications) > 0): ?>
                    <span class="badge bg-warning"><?= count($unreadNotifications) ?></span>
                  <?php endif; ?>
                </a>
                <?php if ($userRole === 'Administrator'): ?>
                  <a href="audit_logs.php" class="nav-link">📋 Audit Logs</a>
                <?php endif; ?>
                <a href="api/logout.php" class="nav-link logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <section class="card">
            <h2>Personal Information</h2>
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <input type="text" class="form-control" id="role" 
                                   value="<?= htmlspecialchars($user['role']) ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="created" class="form-label">Member Since</label>
                            <input type="text" class="form-control" id="created" 
                                   value="<?= date('M j, Y', $user['created']) ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="2fa_status" class="form-label">2FA Status</label>
                            <input type="text" class="form-control" id="2fa_status" 
                                   value="<?= !empty($user['totp_secret']) ? 'Enabled' : 'Disabled' ?>" readonly>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-success">Update Profile</button>
            </form>
        </section>

        <section class="card">
            <h2>Change Password</h2>
            <form method="POST" id="passwordForm">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-warning">Change Password</button>
            </form>
        </section>

        <section class="card">
            <h2>Security Settings</h2>
            <div class="row">
                <div class="col-md-6">
                    <h4>Two-Factor Authentication</h4>
                    <?php if (!empty($user['totp_secret'])): ?>
                        <p class="text-success">✅ 2FA is currently enabled</p>
                        <p class="text-muted">Your account is protected with two-factor authentication.</p>
                        <a href="security.php" class="btn btn-outline-primary">Manage 2FA Settings</a>
                    <?php else: ?>
                        <p class="text-warning">⚠️ 2FA is currently disabled</p>
                        <p class="text-muted">Enable two-factor authentication for enhanced security.</p>
                        <a href="security.php" class="btn btn-success">Enable 2FA</a>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <h4>Account Security</h4>
                    <ul class="list-unstyled">
                        <li>✅ Strong password required</li>
                        <li>✅ Email verification</li>
                        <li><?= !empty($user['totp_secret']) ? '✅' : '❌' ?> Two-factor authentication</li>
                        <li>✅ Secure session management</li>
                    </ul>
                </div>
            </div>
        </section>
    </main>

    <!-- Dark Mode Toggle -->
    <div class="dark-mode-toggle" onclick="toggleDarkMode()">
        <span id="darkModeIcon">🌙</span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dark mode persistence
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

        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword && newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
            }
        });
    </script>
</body>
</html>
