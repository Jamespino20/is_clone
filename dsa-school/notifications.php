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
    <script src="../assets/js/toast.js"></script>
</head>
<body>
    <?php $subtitle = 'Notifications'; $assetPrefix = ''; $unreadNotifications = array_filter($userNotifications, fn($n)=>!$n['read']); include __DIR__ . '/partials/header.php'; ?>

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

                <!-- Preset Roles Section -->
                <div class="row">
                    <div class="col-md-12">
                        <label class="form-label">Quick Add Recipients by Role</label>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRoleRecipients('all_students')">All Students</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRoleRecipients('grade_7_students')">Grade 7</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRoleRecipients('grade_8_students')">Grade 8</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRoleRecipients('grade_9_students')">Grade 9</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRoleRecipients('grade_10_students')">Grade 10</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRoleRecipients('kinder_students')">Kinder</button>
                        </div>
                        <select id="recipients" name="recipients[]" class="form-control" multiple style="min-height: 100px;">
                            <option value="">Select recipients...</option>
                        </select>
                        <small class="text-muted">Selected recipients will appear above. Use Ctrl+Click to select multiple.</small>
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

        <!-- Sent Notifications (Admin/Staff only) -->
        <?php if (has_permission($userRole, 'Staff')): ?>
        <section class="card">
            <h2>Sent Notifications</h2>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Recipients</th>
                            <th>Sent Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="sentNotificationsTable">
                        <tr>
                            <td colspan="4" class="text-center text-muted">Loading sent notifications...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>

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
            formData.append('action', 'send');

            // Handle multiple recipients
            const recipientsSelect = document.getElementById('recipients');
            const recipients = Array.from(recipientsSelect.selectedOptions)
                .filter(option => option.value)
                .map(option => option.value);

            if (recipients.length === 0) {
                showWarning('Please select at least one recipient.');
                return;
            }

            // For now, we'll send individual notifications to each recipient
            // In a real implementation, you might want to batch these
            const promises = recipients.map(recipient => {
                const recipientFormData = new FormData();
                recipientFormData.append('action', 'send_notification');
                recipientFormData.append('target_email', recipient);
                recipientFormData.append('title', formData.get('title'));
                recipientFormData.append('message', formData.get('message'));
                recipientFormData.append('type', formData.get('type'));

                return fetch('api/notifications_api.php', {
                    method: 'POST',
                    body: recipientFormData
                }).then(response => response.json());
            });

            Promise.all(promises)
            .then(results => {
                const successCount = results.filter(r => r.ok).length;
                if (successCount === recipients.length) {
                    showSuccess(`Notification sent successfully to ${successCount} recipients!`);
                    this.reset();
                    // Clear recipients select
                    recipientsSelect.innerHTML = '<option value="">Select recipients...</option>';
                } else {
                    showWarning('Some notifications failed to send. Please try again.');
                }
            })
            .catch(error => {
                showError('Error: ' + error.message);
            });
        });
        
        function markAsRead(notificationId) {
            const form = new URLSearchParams({ action:'mark_read', index: notificationId });
            fetch('api/notifications_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: form
            })
            .then(response => response.json())
            .then(data => {
                if (data.ok) {
                    location.reload();
                } else {
                    showError('Error marking notification as read');
                }
            });
        }

        // Student search functionality for notifications
        let allStudents = [];
        let studentSearchTimeout = null;

        async function loadStudentsForSearch() {
            try {
                console.log('Loading students for search...');
                const response = await fetch('api/students_api.php?action=list', {
                    credentials: 'same-origin'
                });
                const data = await response.json();
                console.log('Students API response:', data);
                if (data.ok) {
                    allStudents = data.items || [];
                    console.log('Students loaded for search:', allStudents.length);
                } else {
                    console.error('Students API error:', data.error);
                }
            } catch (error) {
                console.error('Error loading students:', error);
            }
        }

        // Function to add preset role-based recipients
        function addRoleRecipients(role) {
            const recipientsSelect = document.getElementById('recipients');
            if (!recipientsSelect) return;

            // Role-based email patterns (you can customize these)
            const roleEmails = {
                'all_students': allStudents.map(s => s.email),
                'grade_7_students': allStudents.filter(s => s.grade_level === 'Grade 7').map(s => s.email),
                'grade_8_students': allStudents.filter(s => s.grade_level === 'Grade 8').map(s => s.email),
                'grade_9_students': allStudents.filter(s => s.grade_level === 'Grade 9').map(s => s.email),
                'grade_10_students': allStudents.filter(s => s.grade_level === 'Grade 10').map(s => s.email),
                'kinder_students': allStudents.filter(s => s.grade_level === 'Kinder').map(s => s.email)
            };

            const emails = roleEmails[role] || [];
            if (emails.length === 0) {
                showWarning('No students found for the selected role.');
                return;
            }

            emails.forEach(email => {
                const option = document.createElement('option');
                option.value = email;
                option.textContent = email;
                option.selected = true;
                recipientsSelect.appendChild(option);
            });

            showSuccess(`Added ${emails.length} recipients for ${role.replace('_', ' ')}.`);
        }

        // Enhanced recent activity loading
        async function loadRecentActivity() {
            try {
                const response = await fetch('api/activity_api.php?action=recent');
                const data = await response.json();

                if (data.ok && data.activities) {
                    renderRecentActivity(data.activities);
                } else {
                    // Fallback to showing system activities
                    const activities = <?= json_encode($userActivities) ?>;

                    renderRecentActivity(activities);
                }
            } catch (error) {
                console.error('Error loading recent activity:', error);
                // Fallback to PHP-generated activities
                const activities = <?= json_encode($userActivities) ?>;

                renderRecentActivity(activities);
            }
        }

        function renderRecentActivity(activities) {
            const activityList = document.querySelector('.activity-list');
            if (!activityList) return;

            if (!activities || activities.length === 0) {
                activityList.innerHTML = '<div class="text-center text-muted py-4"><p>No recent activity to display.</p></div>';
                return;
            }

            const activityHtml = activities.slice(0, 10).map(activity => {
                const actionIcons = {
                    'login': '🔑',
                    'logout': '🚪',
                    'accessed_dashboard': '📊',
                    'sent_notification': '📢',
                    'viewed_student': '👨‍🎓',
                    'updated_student': '✏️',
                    'created_student': '➕',
                    'deleted_student': '🗑️',
                    'marked_attendance': '✅',
                    'generated_report': '📈',
                    'changed_settings': '⚙️'
                };

                const icon = actionIcons[activity.action] || '📋';
                const details = activity.details ? `<p class="text-muted">${activity.details}</p>` : '';

                return `
                    <div class="activity-item">
                        <span class="activity-icon">${icon}</span>
                        <div class="activity-content">
                            <p><strong>${formatActivityAction(activity.action)}</strong></p>
                            ${details}
                            <small class="text-muted">${new Date(activity.timestamp * 1000).toLocaleDateString('en-US', {
                                month: 'short',
                                day: 'numeric',
                                year: 'numeric',
                                hour: 'numeric',
                                minute: '2-digit',
                                hour12: true
                            })}</small>
                        </div>
                    </div>
                `;
            }).join('');

            activityList.innerHTML = activityHtml;
        }

        function formatActivityAction(action) {
            const actionMap = {
                'accessed_dashboard': 'Accessed Dashboard',
                'sent_notification': 'Sent Notification',
                'viewed_student': 'Viewed Student Record',
                'updated_student': 'Updated Student Information',
                'created_student': 'Added New Student',
                'deleted_student': 'Removed Student',
                'marked_attendance': 'Marked Attendance',
                'generated_report': 'Generated Report',
                'changed_settings': 'Modified Settings',
                'login': 'Logged In',
                'logout': 'Logged Out'
            };

            return actionMap[action] || action.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }

        // Load sent notifications for admin users
        async function loadSentNotifications() {
            try {
                // For now, we'll show a placeholder since we don't have a sent notifications API yet
                // In a real implementation, this would fetch sent notifications from the database
                const sentTable = document.querySelector('#sentNotificationsTable');
                if (!sentTable) {
                    console.log('Sent notifications table not found - this is normal for non-admin users');
                    return;
                }

                sentTable.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center text-muted">
                            Sent notifications will appear here.<br>
                            <small>This feature will be enhanced in a future update.</small>
                        </td>
                    </tr>
                `;
            } catch (error) {
                console.error('Error loading sent notifications:', error);
            }
        }

        // Load students and activity on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadStudentsForSearch();
            loadRecentActivity();
            loadSentNotifications();
        });
