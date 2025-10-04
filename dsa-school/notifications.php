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
    header('Location: ../index.php');
    exit;
}

$dsManager = DataStructuresManager::getInstance();
$userRole = get_role_display_name($user['role']);

// Handle notification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'mark_read') {
        $notificationId = $_POST['notification_id'] ?? '';
        // In a real system, you'd update the database
        echo json_encode(['ok' => true]);
        exit;
    }
    
    if ($action === 'send_notification') {
        $targetEmail = $_POST['target_email'] ?? '';
        $title = $_POST['title'] ?? '';
        $message = $_POST['message'] ?? '';
        $type = $_POST['type'] ?? 'info';
        
        if ($targetEmail && $title && $message) {
            $dsManager->addNotification($targetEmail, $title, $message, $type);
            $dsManager->logActivity($email, 'sent_notification', "To: $targetEmail, Title: $title");
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Missing fields']);
        }
        exit;
    }
}

// Get notifications for current user including system notifications
$allNotifications = $dsManager->getNotificationQueue()->getAll();
$userNotifications = array_filter($allNotifications, function($n) use ($email, $user) {
    // Include personal notifications and system notifications for this user's role
    return $n['user_email'] === $email || 
           (isset($n['is_system']) && $n['is_system'] && 
            (empty($n['target_roles']) || in_array($user['role'], $n['target_roles'])));
});

// Get recent activities
$recentActivities = $dsManager->getActivityStack()->getAll();
$userActivities = array_slice(array_filter($recentActivities, fn($a) => $a['user_email'] === $email), 0, 10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Notifications - St. Luke's School</title>
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
                <span class="topbar-subtitle">Notifications</span>
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
                  <?php $unread = array_filter($userNotifications, fn($n) => !$n['read']); if (count($unread) > 0): ?>
                    <span class="badge bg-warning"><?= count($unread) ?></span>
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
        <!-- Send Notification (Admin/Staff only) -->
        <?php if (has_permission($userRole, 'Staff')): ?>
        <section class="card">
            <h2>📢 Send Notification</h2>
            <form id="sendNotificationForm">
                <div class="row">
                    <div class="col-md-6">
                        <label for="targetEmail" class="form-label">Recipient Email</label>
                        <input type="email" id="targetEmail" name="target_email" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="notificationType" class="form-label">Type</label>
                        <select id="notificationType" name="type" class="form-control">
                            <option value="info">Information</option>
                            <option value="warning">Warning</option>
                            <option value="success">Success</option>
                            <option value="error">Error</option>
                            <option value="reminder">Reminder</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <label for="notificationTitle" class="form-label">Title</label>
                        <input type="text" id="notificationTitle" name="title" class="form-control" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <label for="notificationMessage" class="form-label">Message</label>
                        <textarea id="notificationMessage" name="message" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-success mt-3">Send Notification</button>
            </form>
        </section>
        <?php endif; ?>

        <!-- My Notifications -->
        <section class="card">
            <h2>📬 My Notifications</h2>
            <div class="notifications-list">
                <?php if (empty($userNotifications)): ?>
                    <div class="text-center text-muted py-4">
                        <h4>No notifications yet</h4>
                        <p>You'll see important updates and messages here.</p>
                    </div>
                <?php else: ?>
                    <?php foreach (array_reverse($userNotifications) as $index => $notification): ?>
                    <div class="notification-item <?= !$notification['read'] ? 'unread' : '' ?>" data-id="<?= $index ?>">
                        <span class="notification-icon">
                            <?php
                            $icons = [
                                'info' => 'ℹ️',
                                'warning' => '⚠️',
                                'success' => '✅',
                                'error' => '❌',
                                'reminder' => '🔔'
                            ];
                            echo $icons[$notification['type']] ?? '📢';
                            ?>
                        </span>
                        <div class="notification-content">
                            <p><strong><?= htmlspecialchars($notification['title']) ?></strong></p>
                            <p><?= htmlspecialchars($notification['message']) ?></p>
                            <small class="text-muted"><?= date('M j, Y \a\t g:i A', $notification['timestamp']) ?></small>
                        </div>
                        <?php if (!$notification['read']): ?>
                        <button class="btn btn-sm btn-outline-primary" onclick="markAsRead(<?= $index ?>)">Mark Read</button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Recent Activity -->
        <section class="card">
            <h2>📊 Recent Activity</h2>
            <div class="activity-list">
                <?php if (empty($userActivities)): ?>
                    <div class="text-center text-muted py-4">
                        <p>No recent activity to display.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($userActivities as $activity): ?>
                    <div class="activity-item">
                        <span class="activity-icon">🔍</span>
                        <div class="activity-content">
                            <p><strong><?= htmlspecialchars($activity['action']) ?></strong></p>
                            <?php if ($activity['details']): ?>
                                <p class="text-muted"><?= htmlspecialchars($activity['details']) ?></p>
                            <?php endif; ?>
                            <small class="text-muted"><?= date('M j, Y \a\t g:i A', $activity['timestamp']) ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- System Statistics (Admin only) -->
        <?php if ($userRole === 'Administrator'): ?>
        <section class="card">
            <h2>📈 System Statistics</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">📢</div>
                    <div class="stat-content">
                        <h3><?= $dsManager->getNotificationQueue()->size() ?></h3>
                        <p>Total Notifications</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3><?= $dsManager->getActivityStack()->size() ?></h3>
                        <p>Activity Records</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📝</div>
                    <div class="stat-content">
                        <h3><?= $dsManager->getSystemLogStack()->size() ?></h3>
                        <p>System Logs</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <h3><?= $dsManager->getPaymentQueue()->size() ?></h3>
                        <p>Payment Records</p>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <!-- Dark Mode Toggle -->
    <div class="dark-mode-toggle" onclick="toggleDarkMode()">
        <span id="darkModeIcon">🌙</span>
    </div>

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

        document.getElementById('sendNotificationForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'send_notification');
            
            fetch('notifications.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.ok) {
                    alert('Notification sent successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                alert('Error: ' + error);
            });
        });
        
        function markAsRead(notificationId) {
            fetch('notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=mark_read&notification_id=${notificationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.ok) {
                    location.reload();
                } else {
                    alert('Error marking notification as read');
                }
            });
        }
    </script>
</body>
</html>
