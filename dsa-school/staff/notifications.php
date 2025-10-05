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
if (!$user || !has_permission(get_role_display_name($user['role']), 'Staff')) {
    header('Location: ../dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Send Notifications - St. Luke's School</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php
        require_once __DIR__ . '/../api/data_structures.php';
        $dsManager = DataStructuresManager::getInstance();
        $userRole = get_role_display_name($user['role']);
        $userNotifications = array_filter($dsManager->getNotificationQueue()->getAll(), function($n) use ($email) {
            return $n['user_email'] === $email;
        });
        $unreadNotifications = array_filter($userNotifications, function($n) {
            return !$n['read'];
        });
        $subtitle = 'Send Notifications'; $assetPrefix = '..'; include __DIR__ . '/../partials/header.php';
    ?>

    <main class="container">
        <section class="card">
            <h2>Send Student Notification</h2>
            <form id="notificationForm">
                <div class="mb-3">
                    <label class="form-label">Subject</label>
                    <input type="text" class="form-control" id="subject" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea class="form-control" id="message" rows="5" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Recipients</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="recipientSearch" placeholder="Search students by name or email...">
                        <button class="btn btn-outline-secondary" type="button" onclick="addSelectedRecipient()">Add</button>
                    </div>
                    <select class="form-control mt-2" id="recipients" multiple required style="min-height: 120px;">
                        <option value="">Select recipients...</option>
                    </select>
                    <small class="text-muted">Selected recipients will appear above</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Priority</label>
                    <select class="form-control" id="priority">
                        <option value="low">Low</option>
                        <option value="normal" selected>Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-success">📢 Send Notification</button>
            </form>
        </section>

        <section class="card">
            <h2>Notification Templates</h2>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="action-card">
                        <h5>📅 Event Reminder</h5>
                        <p>Remind students about upcoming school events</p>
                        <button class="btn btn-sm btn-outline-primary" onclick="useTemplate('event')">Use Template</button>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="action-card">
                        <h5>💰 Payment Reminder</h5>
                        <p>Notify students about pending tuition payments</p>
                        <button class="btn btn-sm btn-outline-primary" onclick="useTemplate('payment')">Use Template</button>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="action-card">
                        <h5>📋 Document Request</h5>
                        <p>Request documents from students</p>
                        <button class="btn btn-sm btn-outline-primary" onclick="useTemplate('document')">Use Template</button>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="action-card">
                        <h5>⚠️ Important Announcement</h5>
                        <p>Send urgent school announcements</p>
                        <button class="btn btn-sm btn-outline-primary" onclick="useTemplate('announcement')">Use Template</button>
                    </div>
                </div>
            </div>
        </section>

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

            // Load students for autocomplete
            loadStudentsForSearch();
            // Load sent notifications
            loadSentNotifications();
        });

        let allStudents = [];

        async function loadStudentsForSearch() {
            try {
                const response = await fetch('../api/students_api.php?action=list');
                const data = await response.json();
                if (data.ok) {
                    allStudents = data.items || [];
                }
            } catch (error) {
                console.error('Error loading students:', error);
            }
        }

        function addSelectedRecipient() {
            const searchInput = document.getElementById('recipientSearch');
            const recipientsSelect = document.getElementById('recipients');
            const searchTerm = searchInput.value.trim().toLowerCase();

            if (!searchTerm) return;

            // Find matching students
            const matches = allStudents.filter(student =>
                student.name.toLowerCase().includes(searchTerm) ||
                student.email.toLowerCase().includes(searchTerm) ||
                student.student_id.toLowerCase().includes(searchTerm)
            ).slice(0, 5); // Limit to 5 results

            if (matches.length > 0) {
                matches.forEach(student => {
                    const option = document.createElement('option');
                    option.value = student.email;
                    option.textContent = `${student.name} (${student.student_id})`;
                    option.selected = true;
                    recipientsSelect.appendChild(option);
                });
                searchInput.value = '';
                updateRecipientsDisplay();
            } else {
                alert('No students found matching your search.');
            }
        }

        function updateRecipientsDisplay() {
            const recipientsSelect = document.getElementById('recipients');
            const selectedOptions = Array.from(recipientsSelect.selectedOptions);

            if (selectedOptions.length > 0) {
                recipientsSelect.options[0].remove(); // Remove placeholder
            }
        }

        async function loadSentNotifications() {
            try {
                // For now, we'll show a placeholder since we don't have a sent notifications API yet
                // In a real implementation, this would fetch sent notifications from the database
                const tbody = document.querySelector('#sentNotificationsTable tbody');
                tbody.innerHTML = `
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

        document.getElementById('notificationForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const subject = document.getElementById('subject').value;
            const message = document.getElementById('message').value;
            const recipients = Array.from(document.getElementById('recipients').selectedOptions).map(opt => opt.value);

            if (recipients.length === 0) {
                alert('Please select at least one recipient.');
                return;
            }

            // Send notification to each selected recipient
            const promises = recipients.map(recipient => {
                const formData = new FormData();
                formData.append('action', 'send');
                formData.append('target_email', recipient);
                formData.append('title', subject);
                formData.append('message', message);
                formData.append('type', 'info');

                return fetch('../api/notifications_api.php', {
                    method: 'POST',
                    body: formData
                }).then(response => response.json());
            });

            Promise.all(promises)
            .then(results => {
                const successCount = results.filter(r => r.ok).length;
                if (successCount === recipients.length) {
                    alert(`Notification sent successfully to ${successCount} recipients!`);
                    document.getElementById('notificationForm').reset();
                    loadSentNotifications();
                } else {
                    alert('Some notifications failed to send. Please try again.');
                }
            })
            .catch(error => {
                alert('Error: ' + error);
            });
        });

        function useTemplate(type) {
            const templates = {
                event: {
                    subject: 'Upcoming School Event',
                    message: 'Dear Students,\n\nWe would like to remind you about our upcoming school event...'
                },
                payment: {
                    subject: 'Tuition Payment Reminder',
                    message: 'Dear Students,\n\nThis is a friendly reminder about your pending tuition payment...'
                },
                document: {
                    subject: 'Document Request',
                    message: 'Dear Students,\n\nPlease submit the following documents...'
                },
                announcement: {
                    subject: 'Important Announcement',
                    message: 'Dear Students,\n\nWe have an important announcement...'
                }
            };
            
            if (templates[type]) {
                document.getElementById('subject').value = templates[type].subject;
                document.getElementById('message').value = templates[type].message;
            }
        }
    </script>
</body>
</html>
