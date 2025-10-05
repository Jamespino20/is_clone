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

        document.getElementById('notificationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const title = document.getElementById('notifTitle').value;
            const message = document.getElementById('notifMessage').value;
            const type = document.getElementById('notifType').value;
            const targets = Array.from(document.getElementById('notifTarget').selectedOptions).map(opt => opt.value);
            
            if (confirm(`Send "${title}" notification to ${targets.join(', ')}?`)) {
                alert('Notification sent successfully!');
                this.reset();
            }
        });
    </script>
</body>
</html>
