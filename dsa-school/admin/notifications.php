<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../api/helpers.php';

$email = $_SESSION['user_email'] ?? null;
if (!$email) {
    header('Location: ../index.php');
    exit;
}

$user = get_user_by_email($email);
if (!$user || $user['role'] !== 'Administrator') {
    header('Location: ../dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Notifications - St. Luke's School</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="../assets/js/toast.js"></script>
</head>
<body>
    <?php $subtitle = 'Manage Notifications'; $assetPrefix = '..'; $userRole = get_role_display_name($user['role']); $unreadNotifications = []; include __DIR__ . '/../partials/header.php'; ?>

    <main class="container">
        <section class="card">
            <h2>Send System Notification</h2>
            <form id="notificationForm">
                <div class="mb-3">
                    <label class="form-label">Notification Title</label>
                    <input type="text" class="form-control" id="notifTitle" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea class="form-control" id="notifMessage" rows="4" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select class="form-control" id="notifType">
                        <option value="info">Info</option>
                        <option value="warning">Warning</option>
                        <option value="success">Success</option>
                        <option value="error">Error</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Target Recipients</label>
                    <select class="form-control" id="notifTarget" multiple>
                        <option value="all">All Users</option>
                        <option value="Administrator">Administrators</option>
                        <option value="Staff">Staff</option>
                        <option value="Faculty">Faculty</option>
                        <option value="Student">Students</option>
                    </select>
                    <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                </div>
                <button type="submit" class="btn btn-success">📢 Send Notification</button>
            </form>
        </section>

        <section class="card">
            <h2>View All Notifications</h2>
            <p>View and manage all system notifications.</p>
            <a href="../notifications.php" class="btn btn-primary">View Notifications</a>
        </section>
    </main>

    <div class="dark-mode-toggle" onclick="toggleDarkMode()">
        <span id="darkModeIcon">🌙</span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
        
        document.addEventListener('DOMContentLoaded', function() {
            const darkMode = localStorage.getItem('darkMode');
            if (darkMode === 'true') {
                document.body.classList.add('dark-mode');
                document.getElementById('darkModeIcon').textContent = '☀️';
            }
        });

        document.getElementById('notificationForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const title = document.getElementById('notifTitle').value;
            const message = document.getElementById('notifMessage').value;
            const type = document.getElementById('notifType').value;
            const targets = Array.from(document.getElementById('notifTarget').selectedOptions).map(opt => opt.value);
            
            if (!targets.length) {
                showError('Please select at least one target recipient');
                return;
            }
            
            if (!confirm(`Send "${title}" notification to ${targets.join(', ')}?`)) {
                return;
            }
            
            try {
                const usersResponse = await fetch('../api/users_api.php?action=list');
                const data = await usersResponse.json();
                
                if (!data.ok) {
                    showError('Failed to fetch users: ' + (data.error || 'Unknown error'));
                    return;
                }
                
                const users = data.users;
                
                let recipients = [];
                if (targets.includes('all')) {
                    recipients = users;
                } else {
                    recipients = users.filter(user => targets.includes(user.role));
                }
                
                if (recipients.length === 0) {
                    showError('No recipients found for selected targets');
                    return;
                }
                
                let successCount = 0;
                let errorCount = 0;
                
                for (const recipient of recipients) {
                    const formData = new FormData();
                    formData.append('action', 'send');
                    formData.append('target_email', recipient.email);
                    formData.append('title', title);
                    formData.append('message', message);
                    formData.append('type', type);
                    
                    try {
                        const response = await fetch('../api/notifications_api.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();
                        
                        if (data.ok) {
                            successCount++;
                        } else {
                            errorCount++;
                            console.error(`Failed to send to ${recipient.email}:`, data.error);
                        }
                    } catch (err) {
                        errorCount++;
                        console.error(`Error sending to ${recipient.email}:`, err);
                    }
                }
                
                if (successCount > 0) {
                    showSuccess(`Notification sent successfully to ${successCount} recipient(s)!`);
                    this.reset();
                }
                if (errorCount > 0) {
                    showError(`Failed to send to ${errorCount} recipient(s). Check console for details.`);
                }
            } catch (error) {
                showError('Error: ' + error.message);
                console.error('Notification send error:', error);
            }
        });
    </script>
</body>
</html>
