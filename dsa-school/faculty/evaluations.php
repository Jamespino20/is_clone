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
    <title>Student Evaluations - St. Luke's School</title>
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
        $subtitle = 'Student Evaluations'; $assetPrefix = '..'; include __DIR__ . '/../partials/header.php';
    ?>

    <main class="container">
        <section class="card">
            <h2>My Teaching Evaluations</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">⭐</div>
                    <div class="stat-content">
                        <h3>4.8</h3>
                        <p>Overall Rating</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3>45</h3>
                        <p>Total Evaluations</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📈</div>
                    <div class="stat-content">
                        <h3>+0.3</h3>
                        <p>Improvement</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📝</div>
                    <div class="stat-content">
                        <h3>12</h3>
                        <p>Written Feedback</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="card">
            <h2>Evaluation Categories</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Rating</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Teaching Effectiveness</td>
                            <td>
                                <span class="badge bg-success">4.9 / 5.0</span>
                            </td>
                            <td><button class="btn btn-sm btn-outline-primary" onclick="viewDetails('teaching')">View Details</button></td>
                        </tr>
                        <tr>
                            <td>Course Organization</td>
                            <td>
                                <span class="badge bg-success">4.7 / 5.0</span>
                            </td>
                            <td><button class="btn btn-sm btn-outline-primary" onclick="viewDetails('organization')">View Details</button></td>
                        </tr>
                        <tr>
                            <td>Student Engagement</td>
                            <td>
                                <span class="badge bg-success">4.8 / 5.0</span>
                            </td>
                            <td><button class="btn btn-sm btn-outline-primary" onclick="viewDetails('engagement')">View Details</button></td>
                        </tr>
                        <tr>
                            <td>Communication Skills</td>
                            <td>
                                <span class="badge bg-success">4.9 / 5.0</span>
                            </td>
                            <td><button class="btn btn-sm btn-outline-primary" onclick="viewDetails('communication')">View Details</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <h2>Recent Feedback</h2>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="action-card">
                        <p><em>"Excellent teacher! Very clear explanations and patient with students."</em></p>
                        <small class="text-muted">Mathematics - <?= date('M j, Y') ?></small>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="action-card">
                        <p><em>"Makes difficult concepts easy to understand. Highly recommended!"</em></p>
                        <small class="text-muted">Science - <?= date('M j, Y', strtotime('-3 days')) ?></small>
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

        function viewDetails(category) {
            showInfo(`Viewing detailed evaluations for ${category}`);
        }
    </script>
</body>
</html>
