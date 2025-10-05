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
if (!$user || $user['role'] !== 'Faculty') {
    header('Location: ../dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Classes - St. Luke's School</title>
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
        $subtitle = 'My Classes'; $assetPrefix = '..'; include __DIR__ . '/../partials/header.php';
    ?>

    <main class="container">
        <section class="card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Teaching Schedule</h2>
                <button class="btn btn-success" onclick="alert('Contact administrator to assign you to classes')">Request Class Assignment</button>
            </div>
            
            <div id="classList" class="row">
                <div class="col-12 text-center py-4">Loading classes...</div>
            </div>
        </section>

        <section class="card">
            <h2>Class Statistics</h2>
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3 id="statTotalClasses">0</h3>
                        <p>Total Classes</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3 id="statTotalStudents">0</h3>
                        <p>Total Students</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3 id="statSubjects">0</h3>
                        <p>Subjects</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3 id="statAvgClass">0</h3>
                        <p>Avg Class Size</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div class="dark-mode-toggle" onclick="toggleDarkMode()">
        <span id="darkModeIcon">🌙</span>
    </div>

    <script>
        let classes = [];

        async function loadClasses() {
            try {
                const res = await fetch('../api/faculty_api.php?action=my_classes');
                const data = await res.json();
                
                if (data.ok && data.classes) {
                    classes = data.classes;
                    renderClasses();
                    updateStats();
                } else {
                    document.getElementById('classList').innerHTML = `
                        <div class="col-12 text-center text-muted py-4">
                            <p>No classes assigned yet.</p>
                            <p>Contact your administrator to be assigned to classes.</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading classes:', error);
                document.getElementById('classList').innerHTML = `
                    <div class="col-12 text-center text-danger py-4">
                        Error loading classes. Please try again.
                    </div>
                `;
            }
        }

        function renderClasses() {
            const container = document.getElementById('classList');
            
            if (classes.length === 0) {
                container.innerHTML = `
                    <div class="col-12 text-center text-muted py-4">
                        <p>No classes assigned yet.</p>
                        <p>Contact your administrator to be assigned to classes.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = classes.map((cls, index) => {
                const gradeLevels = [...new Set(cls.students.map(s => s.grade_level).filter(g => g))];
                const gradeDisplay = gradeLevels.length > 0 ? gradeLevels.join(', ') : 'Multiple Grades';
                
                return `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="class-card">
                            <div class="class-header">
                                <h3>${escapeHtml(cls.subject)}</h3>
                                <span class="badge bg-primary">${cls.student_count} Students</span>
                            </div>
                            <div class="class-details">
                                <p><strong>Grade Levels:</strong> ${escapeHtml(gradeDisplay)}</p>
                                <p><strong>Students Enrolled:</strong> ${cls.student_count}</p>
                            </div>
                            <div class="class-actions">
                                <button class="btn btn-primary btn-sm" onclick="viewClassDetails(${index})">View Students</button>
                                <a href="../faculty/grades.php?subject=${encodeURIComponent(cls.subject)}" class="btn btn-outline-primary btn-sm">Grades</a>
                                <a href="../faculty/attendance.php?subject=${encodeURIComponent(cls.subject)}" class="btn btn-outline-secondary btn-sm">Attendance</a>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function viewClassDetails(index) {
            const cls = classes[index];
            if (!cls) return;
            
            const studentList = cls.students.map(s => 
                `<li>${escapeHtml(s.name)} (${escapeHtml(s.student_id || 'N/A')}) - ${escapeHtml(s.grade_level || 'N/A')}</li>`
            ).join('');
            
            const msg = `
                <h4>${escapeHtml(cls.subject)}</h4>
                <p><strong>${cls.student_count} Students:</strong></p>
                <ul>${studentList}</ul>
            `;
            
            const modal = document.createElement('div');
            modal.className = 'modal fade show';
            modal.style.display = 'block';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Class Details</h5>
                            <button type="button" class="btn-close" onclick="this.closest('.modal').remove()"></button>
                        </div>
                        <div class="modal-body">${msg}</div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        function updateStats() {
            const totalClasses = classes.length;
            const totalStudents = classes.reduce((sum, cls) => sum + cls.student_count, 0);
            const avgClassSize = totalClasses > 0 ? Math.round(totalStudents / totalClasses) : 0;
            
            document.getElementById('statTotalClasses').textContent = totalClasses;
            document.getElementById('statTotalStudents').textContent = totalStudents;
            document.getElementById('statSubjects').textContent = totalClasses;
            document.getElementById('statAvgClass').textContent = avgClassSize;
        }

        function escapeHtml(text) {
            const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return String(text || '').replace(/[&<>"']/g, m => map[m]);
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
            
            loadClasses();
        });
    </script>

    <style>
        .class-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 8px;
            padding: 1.5rem;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .class-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .class-header h3 {
            margin: 0;
            font-size: 1.25rem;
            color: var(--color-success);
        }

        .class-details {
            flex: 1;
            margin-bottom: 1rem;
        }

        .class-details p {
            margin: 0.5rem 0;
            color: var(--color-text-muted);
        }

        .class-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .stat-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
        }

        .stat-card h3 {
            margin: 0;
            font-size: 2rem;
            color: var(--color-success);
        }

        .stat-card p {
            margin: 0.5rem 0 0 0;
            color: var(--color-text-muted);
        }

        .modal.show {
            background: rgba(0,0,0,0.5);
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
