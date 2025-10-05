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
if (!$user || !has_permission(get_role_display_name($user['role']), 'Faculty')) {
    header('Location: ../dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Assignments - St. Luke's School</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="../assets/js/toast.js"></script>
</head>
<body>
    <?php
        require_once __DIR__ . '/../api/data_structures.php';
        $dsManager = DataStructuresManager::getInstance();
        $userRole = get_role_display_name($user['role']);
        $userNotifications = array_filter($dsManager->getNotificationQueue()->getAll(), fn($n) => $n['user_email'] === $email);
        $unreadNotifications = array_filter($userNotifications, fn($n) => !$n['read']);
        $subtitle = 'Assignments'; $assetPrefix = '..'; include __DIR__ . '/../partials/header.php';
    ?>

    <main class="container">
        <section class="card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>My Assignments</h2>
                <button class="btn btn-success" onclick="createAssignment()">+ Create Assignment</button>
            </div>
            
            <div class="row" id="assignmentsList">
                <div class="col-12 text-center">Loading assignments...</div>
            </div>
        </section>

        <section class="card">
            <h2>Assignment Statistics</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">📋</div>
                    <div class="stat-content">
                        <h3 id="statActive">0</h3>
                        <p>Active Assignments</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3 id="statSubmissions">0</h3>
                        <p>Total Submissions</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-content">
                        <h3 id="statPending">0</h3>
                        <p>Pending Review</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3 id="statAvgRate">0%</h3>
                        <p>Avg Completion Rate</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div class="dark-mode-toggle" onclick="toggleDarkMode()">
        <span id="darkModeIcon">🌙</span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let assignments = [];

        async function loadAssignments() {
            try {
                const res = await fetch('../api/assignments_api.php?action=list');
                const data = await res.json();
                if (data.ok && data.items) {
                    assignments = data.items;
                    renderAssignments();
                    updateStats();
                } else {
                    document.getElementById('assignmentsList').innerHTML = '<div class="col-12 text-center text-muted">No assignments yet</div>';
                }
            } catch (error) {
                console.error('Error loading assignments:', error);
                document.getElementById('assignmentsList').innerHTML = '<div class="col-12 text-center text-danger">Error loading assignments</div>';
            }
        }

        function renderAssignments() {
            const container = document.getElementById('assignmentsList');
            if (assignments.length === 0) {
                container.innerHTML = '<div class="col-12 text-center text-muted">No assignments yet. Create one to get started!</div>';
                return;
            }

            container.innerHTML = assignments.map(assignment => {
                const dueDate = new Date(assignment.due_date * 1000).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                const statusClass = assignment.status === 'Completed' ? 'success' : 'warning';
                return `
                    <div class="col-md-6 mb-3">
                        <div class="action-card">
                            <div class="d-flex justify-content-between">
                                <h5>📝 ${escapeHtml(assignment.title || '')}</h5>
                                <span class="badge bg-${statusClass}">${escapeHtml(assignment.status || '')}</span>
                            </div>
                            <p class="text-muted">Due: ${dueDate}</p>
                            <p>${escapeHtml(assignment.subject || '')} - ${escapeHtml(assignment.grade_level || '')}</p>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary" onclick="viewSubmissions(${assignment.id})">View Submissions (${assignment.submissions || 0}/${assignment.total_students || 30})</button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="editAssignment(${assignment.id})">Edit</button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteAssignment(${assignment.id})">Delete</button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function updateStats() {
            const active = assignments.filter(a => a.status === 'Ongoing').length;
            const totalSub = assignments.reduce((sum, a) => sum + (a.submissions || 0), 0);
            const totalPending = assignments.reduce((sum, a) => sum + ((a.total_students || 30) - (a.submissions || 0)), 0);
            const avgRate = assignments.length > 0 
                ? Math.round(assignments.reduce((sum, a) => sum + ((a.submissions || 0) / (a.total_students || 30) * 100), 0) / assignments.length)
                : 0;

            document.getElementById('statActive').textContent = active;
            document.getElementById('statSubmissions').textContent = totalSub;
            document.getElementById('statPending').textContent = totalPending;
            document.getElementById('statAvgRate').textContent = avgRate + '%';
        }

        function escapeHtml(text) {
            const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        async function createAssignment() {
            const title = prompt('Assignment title:');
            if (!title) return;

            const subject = prompt('Subject (e.g., Mathematics):');
            if (!subject) return;

            const gradeLevel = prompt('Grade level (e.g., Grade 10):');
            if (!gradeLevel) return;

            const dueDate = prompt('Due date (YYYY-MM-DD):');
            if (!dueDate) return;

            const description = prompt('Description (optional):') || '';

            try {
                const formData = new URLSearchParams({
                    title, subject, grade_level: gradeLevel, due_date: dueDate, description
                });

                const res = await fetch('../api/assignments_api.php?action=create', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: formData
                });

                const data = await res.json();
                if (data.ok) {
                    showSuccess('Assignment created successfully!');
                    loadAssignments();
                } else {
                    showError('Error: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                showError('Error creating assignment: ' + error.message);
            }
        }

        function viewSubmissions(id) {
            showInfo('View submissions feature would show submission details for assignment ' + id);
        }

        async function editAssignment(id) {
            const assignment = assignments.find(a => a.id === id);
            if (!assignment) return;

            const title = prompt('Assignment title:', assignment.title) || assignment.title;
            const dueDate = prompt('Due date (YYYY-MM-DD):', new Date(assignment.due_date * 1000).toISOString().split('T')[0]);
            if (!dueDate) return;

            try {
                const formData = new URLSearchParams({ id, title, due_date: dueDate });
                const res = await fetch('../api/assignments_api.php?action=update', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: formData
                });

                const data = await res.json();
                if (data.ok) {
                    showSuccess('Assignment updated!');
                    loadAssignments();
                } else {
                    showError('Error: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                showError('Error updating assignment: ' + error.message);
            }
        }

        async function deleteAssignment(id) {
            if (!confirm('Delete this assignment? This cannot be undone.')) return;

            try {
                const res = await fetch('../api/assignments_api.php?action=delete', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({ id })
                });

                const data = await res.json();
                if (data.ok) {
                    showSuccess('Assignment deleted!');
                    loadAssignments();
                } else {
                    showError('Error: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                showError('Error deleting assignment: ' + error.message);
            }
        }

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
            loadAssignments();
        });
    </script>
</body>
</html>
