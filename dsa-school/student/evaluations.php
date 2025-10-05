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
if (!$user || $user['role'] !== 'Student') {
    header('Location: ../dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Teacher Evaluations - St. Luke's School</title>
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
        $subtitle = 'Teacher Evaluations'; $assetPrefix = '..'; include __DIR__ . '/../partials/header.php';
    ?>

    <main class="container">
        <section class="card">
            <h2>Evaluate Your Teachers</h2>
            <p>Your feedback helps us improve teaching quality. All evaluations are anonymous and confidential.</p>
            
            <div class="alert alert-info">
                <strong>📝 Evaluation Period:</strong> Now open through <?= date('F j, Y', strtotime('+14 days')) ?>
            </div>
        </section>

        <section class="card">
            <h3>Your Teachers</h3>
            <div class="row" id="teachersList">
                <div class="col-12 text-center">Loading teachers...</div>
            </div>
        </section>

        <section class="card">
            <h3>Evaluation Progress</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3 id="statTotal">0</h3>
                        <p>Total Teachers</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3 id="statCompleted">0</h3>
                        <p>Completed</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-content">
                        <h3 id="statPending">0</h3>
                        <p>Pending</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📈</div>
                    <div class="stat-content">
                        <h3 id="statProgress">0%</h3>
                        <p>Progress</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div id="evaluationModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Teacher Evaluation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalBody">
                </div>
            </div>
        </div>
    </div>

    <div class="dark-mode-toggle" onclick="toggleDarkMode()">
        <span id="darkModeIcon">🌙</span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let teachers = [];

        async function loadTeachers() {
            try {
                const res = await fetch('../api/faculty_api.php?action=list_for_student');
                const data = await res.json();
                if (data.ok && data.teachers) {
                    teachers = data.teachers;
                    renderTeachers();
                    updateStats();
                } else {
                    document.getElementById('teachersList').innerHTML = '<div class="col-12 text-center text-muted">No teachers assigned yet</div>';
                }
            } catch (error) {
                console.error('Error loading teachers:', error);
                document.getElementById('teachersList').innerHTML = '<div class="col-12 text-center text-danger">Error loading teachers</div>';
            }
        }

        function renderTeachers() {
            const container = document.getElementById('teachersList');
            container.innerHTML = teachers.map(teacher => `
                <div class="col-md-6 mb-3">
                    <div class="action-card">
                        <h5>${escapeHtml(teacher.name || 'Unknown')}</h5>
                        <p class="text-muted">${escapeHtml(teacher.subject || 'Subject')}</p>
                        ${teacher.evaluated ? `
                            <span class="badge bg-success">✅ Evaluated</span>
                            <button class="btn btn-sm btn-outline-primary mt-2" onclick="viewEvaluation(${teacher.id})">View Submission</button>
                        ` : `
                            <span class="badge bg-warning">⏳ Pending</span>
                            <button class="btn btn-sm btn-primary mt-2" onclick="evaluateTeacher(${teacher.id}, '${escapeHtml(teacher.name || '')}', '${escapeHtml(teacher.subject || '')}')">Evaluate Now</button>
                        `}
                    </div>
                </div>
            `).join('');
        }

        function updateStats() {
            const total = teachers.length;
            const completed = teachers.filter(t => t.evaluated).length;
            const pending = total - completed;
            const progress = total > 0 ? Math.round((completed / total) * 100) : 0;

            document.getElementById('statTotal').textContent = total;
            document.getElementById('statCompleted').textContent = completed;
            document.getElementById('statPending').textContent = pending;
            document.getElementById('statProgress').textContent = progress + '%';
        }

        function escapeHtml(text) {
            const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        async function evaluateTeacher(id, name, subject) {
            const modal = new bootstrap.Modal(document.getElementById('evaluationModal'));
            document.getElementById('modalTitle').textContent = `Evaluate ${name} - ${subject}`;
            
            const questions = [
                'How would you rate the teacher\'s subject knowledge?',
                'How effective is the teacher\'s communication?',
                'How well does the teacher engage students?',
                'How fair is the teacher\'s grading?',
                'How accessible is the teacher for help?'
            ];

            const modalBody = document.getElementById('modalBody');
            modalBody.innerHTML = `
                <form id="evaluationForm">
                    ${questions.map((q, i) => `
                        <div class="mb-3">
                            <label class="form-label"><strong>${i + 1}. ${q}</strong></label>
                            <select class="form-select" name="q${i}" required>
                                <option value="">Select rating...</option>
                                <option value="5">Excellent (5)</option>
                                <option value="4">Very Good (4)</option>
                                <option value="3">Good (3)</option>
                                <option value="2">Fair (2)</option>
                                <option value="1">Poor (1)</option>
                            </select>
                        </div>
                    `).join('')}
                    <div class="mb-3">
                        <label class="form-label"><strong>Additional Comments (Optional)</strong></label>
                        <textarea class="form-control" name="comments" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Evaluation</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </form>
            `;

            document.getElementById('evaluationForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                
                const scores = {};
                questions.forEach((q, i) => {
                    scores[`question_${i + 1}`] = parseInt(formData.get(`q${i}`));
                });
                
                const comments = formData.get('comments') || '';

                try {
                    const teacher = teachers.find(t => t.id === id);
                    const response = await fetch('../api/evaluations_api.php?action=submit', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: new URLSearchParams({
                            teacher_email: teacher.email,
                            scores: JSON.stringify(scores),
                            comments: comments
                        })
                    });

                    const data = await response.json();
                    if (data.ok) {
                        showSuccess('Evaluation submitted successfully!');
                        modal.hide();
                        loadTeachers();
                    } else {
                        showError('Error: ' + (data.error || 'Unknown error'));
                    }
                } catch (error) {
                    showError('Error submitting evaluation: ' + error.message);
                }
            });

            modal.show();
        }

        function viewEvaluation(id) {
            showSuccess('Your evaluation has been submitted and is anonymous. You cannot view it again.');
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
            loadTeachers();
        });
    </script>
</body>
</html>
