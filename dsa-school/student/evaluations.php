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

$teachers = [
    ['id' => 1, 'name' => 'Prof. Maria Santos', 'subject' => 'Mathematics', 'evaluated' => false],
    ['id' => 2, 'name' => 'Prof. Juan Reyes', 'subject' => 'Science', 'evaluated' => false],
    ['id' => 3, 'name' => 'Prof. Ana Garcia', 'subject' => 'English', 'evaluated' => true]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Teacher Evaluations - St. Luke's School</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <div class="row">
                <?php foreach ($teachers as $teacher): ?>
                <div class="col-md-6 mb-3">
                    <div class="action-card">
                        <h5><?= htmlspecialchars($teacher['name']) ?></h5>
                        <p class="text-muted"><?= htmlspecialchars($teacher['subject']) ?></p>
                        <?php if ($teacher['evaluated']): ?>
                            <span class="badge bg-success">✅ Evaluated</span>
                            <button class="btn btn-sm btn-outline-primary mt-2" onclick="viewEvaluation(<?= $teacher['id'] ?>)">View Submission</button>
                        <?php else: ?>
                            <span class="badge bg-warning">⏳ Pending</span>
                            <button class="btn btn-sm btn-primary mt-2" onclick="evaluateTeacher(<?= $teacher['id'] ?>, '<?= htmlspecialchars($teacher['name']) ?>', '<?= htmlspecialchars($teacher['subject']) ?>')">Evaluate Now</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="card">
            <h3>Evaluation Progress</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3><?= count($teachers) ?></h3>
                        <p>Total Teachers</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3><?= count(array_filter($teachers, fn($t) => $t['evaluated'])) ?></h3>
                        <p>Completed</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-content">
                        <h3><?= count(array_filter($teachers, fn($t) => !$t['evaluated'])) ?></h3>
                        <p>Pending</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📈</div>
                    <div class="stat-content">
                        <h3><?= round((count(array_filter($teachers, fn($t) => $t['evaluated'])) / count($teachers)) * 100) ?>%</h3>
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
        let evaluationModal;
        
        document.addEventListener('DOMContentLoaded', function() {
            evaluationModal = new bootstrap.Modal(document.getElementById('evaluationModal'));
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
        
        document.addEventListener('DOMContentLoaded', function() {
            const darkMode = localStorage.getItem('darkMode');
            if (darkMode === 'true') {
                document.body.classList.add('dark-mode');
                document.getElementById('darkModeIcon').textContent = '☀️';
            }
        });

        function evaluateTeacher(id, name, subject) {
            document.getElementById('modalTitle').textContent = `Evaluate ${name}`;
            document.getElementById('modalBody').innerHTML = `
                <p class="mb-4"><strong>Subject:</strong> ${subject}</p>
                <form id="evaluationForm">
                    <input type="hidden" name="teacher_id" value="${id}">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">1. Teaching Effectiveness</label>
                        <p class="text-muted small">How effectively does the teacher explain concepts?</p>
                        <div class="rating-group">
                            ${createRatingButtons('teaching_effectiveness')}
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">2. Course Organization</label>
                        <p class="text-muted small">How well organized are the lessons and materials?</p>
                        <div class="rating-group">
                            ${createRatingButtons('course_organization')}
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">3. Student Engagement</label>
                        <p class="text-muted small">How well does the teacher engage and motivate students?</p>
                        <div class="rating-group">
                            ${createRatingButtons('student_engagement')}
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">4. Communication Skills</label>
                        <p class="text-muted small">How clear and accessible is the teacher's communication?</p>
                        <div class="rating-group">
                            ${createRatingButtons('communication')}
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">5. Availability and Support</label>
                        <p class="text-muted small">How available and supportive is the teacher outside of class?</p>
                        <div class="rating-group">
                            ${createRatingButtons('availability')}
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Additional Comments (Optional)</label>
                        <textarea class="form-control" name="comments" rows="4" placeholder="Share any additional feedback or suggestions..."></textarea>
                    </div>

                    <div class="alert alert-warning">
                        <strong>Note:</strong> Your evaluation is anonymous and will be used solely to improve teaching quality.
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Submit Evaluation</button>
                </form>
            `;
            evaluationModal.show();

            document.getElementById('evaluationForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const ratings = ['teaching_effectiveness', 'course_organization', 'student_engagement', 'communication', 'availability'];
                const allRated = ratings.every(r => document.querySelector(`input[name="${r}"]:checked`));
                
                if (!allRated) {
                    alert('Please rate all criteria before submitting.');
                    return;
                }

                if (!confirm('Submit your evaluation? You will not be able to change it after submission.')) return;
                const scores = {
                    teaching_effectiveness: Number(document.querySelector('input[name="teaching_effectiveness"]:checked').value),
                    course_organization: Number(document.querySelector('input[name="course_organization"]:checked').value),
                    student_engagement: Number(document.querySelector('input[name="student_engagement"]:checked').value),
                    communication: Number(document.querySelector('input[name="communication"]:checked').value),
                    availability: Number(document.querySelector('input[name="availability"]:checked').value)
                };
                const payload = new URLSearchParams({ action:'submit', teacher_email: 'teacher'+id+'@slssr.edu.ph', scores: JSON.stringify(scores), comments: (this.elements['comments']?.value || '') });
                fetch('../api/evaluations_api.php?action=submit', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: payload })
                    .then(r=>r.json()).then(d=>{
                        if (!d.ok) { alert(d.error || 'Failed'); return; }
                        const toast = document.createElement('div');
                        toast.className = 'alert alert-success position-fixed top-0 end-0 m-3';
                        toast.style.zIndex = '9999';
                        toast.innerHTML = '<strong>Success!</strong> Your evaluation has been submitted. Thank you for your feedback!';
                        document.body.appendChild(toast);
                        setTimeout(() => toast.remove(), 5000);
                        evaluationModal.hide();
                        setTimeout(() => location.reload(), 1500);
                    }).catch(()=>alert('Network error'));
            });
        }

        function createRatingButtons(name) {
            const ratings = [
                { value: 5, label: 'Excellent', color: 'success' },
                { value: 4, label: 'Good', color: 'info' },
                { value: 3, label: 'Average', color: 'warning' },
                { value: 2, label: 'Below Average', color: 'orange' },
                { value: 1, label: 'Poor', color: 'danger' }
            ];
            
            return ratings.map(r => `
                <label class="rating-option">
                    <input type="radio" name="${name}" value="${r.value}" required>
                    <span class="rating-label">${r.label} (${r.value})</span>
                </label>
            `).join('');
        }

        function viewEvaluation(id) {
            document.getElementById('modalTitle').textContent = 'Your Evaluation';
            document.getElementById('modalBody').innerHTML = `
                <div class="alert alert-info">
                    <strong>✅ Evaluation Submitted</strong>
                    <p class="mb-0">You have already submitted your evaluation for this teacher. Thank you for your feedback!</p>
                </div>
                <p>Submitted on: ${new Date().toLocaleDateString()}</p>
                <p class="text-muted">Your responses are kept confidential and used solely for improving teaching quality.</p>
            `;
            evaluationModal.show();
        }
    </script>

    <style>
        .rating-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .rating-option {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .rating-option:hover {
            background-color: #f8f9fa;
            border-color: #0d6efd;
        }
        
        .rating-option input[type="radio"] {
            margin-right: 0.5rem;
        }
        
        .rating-option input[type="radio"]:checked + .rating-label {
            font-weight: bold;
            color: #0d6efd;
        }
    </style>
</body>
</html>
