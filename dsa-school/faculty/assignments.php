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
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="action-card">
                        <div class="d-flex justify-content-between">
                            <h5>📝 Chapter 5 Quiz</h5>
                            <span class="badge bg-warning">Ongoing</span>
                        </div>
                        <p class="text-muted">Due: <?= date('M j, Y', strtotime('+3 days')) ?></p>
                        <p>Mathematics - Grade 10</p>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary">View Submissions (12/30)</button>
                            <button class="btn btn-sm btn-outline-secondary">Edit</button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="action-card">
                        <div class="d-flex justify-content-between">
                            <h5>📚 Research Paper</h5>
                            <span class="badge bg-success">Completed</span>
                        </div>
                        <p class="text-muted">Due: <?= date('M j, Y', strtotime('-5 days')) ?></p>
                        <p>English - Grade 10</p>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary">View Submissions (28/30)</button>
                            <button class="btn btn-sm btn-outline-secondary">Edit</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="card">
            <h2>Assignment Statistics</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">📋</div>
                    <div class="stat-content">
                        <h3>2</h3>
                        <p>Active Assignments</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3>40</h3>
                        <p>Total Submissions</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-content">
                        <h3>20</h3>
                        <p>Pending Review</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3>85%</h3>
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

        function createAssignment() {
            alert('Create Assignment dialog would open here.');
        }
    </script>
</body>
</html>
