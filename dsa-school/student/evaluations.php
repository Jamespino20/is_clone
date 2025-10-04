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
</head>
<body>
    <header class="topbar">
        <div class="topbar-left">
            <img src="../assets/img/school-logo.png" alt="School Logo" class="topbar-logo">
            <div class="topbar-title">
                <h1>St. Luke's School of San Rafael</h1>
                <span class="topbar-subtitle">Teacher Evaluations</span>
            </div>
        </div>
        <div class="topbar-right">
            <div class="user-info">
                <span class="user-name">Welcome, <?= htmlspecialchars($user['name']) ?></span>
                <span class="user-role"><?= get_role_display_name($user['role']) ?></span>
            </div>
            <nav>
                <a href="../profile.php" class="nav-link">Profile</a>
                <a href="../security.php" class="nav-link">Security</a>
                <a href="../notifications.php" class="nav-link">🔔 Notifications</a>
                <a href="../api/logout.php" class="nav-link logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <!-- 404 Error Page -->
        <section class="card text-center py-5">
            <div class="error-404">
                <div class="error-icon">⭐</div>
                <h1 class="display-1 fw-bold text-primary">404</h1>
                <h2 class="h3 mb-4">Teacher Evaluations Coming Soon</h2>
                <p class="lead text-muted mb-4">
                    The teacher evaluation system is currently under development. 
                    This feature will be available soon to help improve our educational experience.
                </p>
                
                <div class="row justify-content-center mb-4">
                    <div class="col-md-8">
                        <div class="alert alert-info">
                            <h5 class="alert-heading">🚧 Under Construction</h5>
                            <p class="mb-0">
                                We're working hard to bring you a comprehensive teacher evaluation system 
                                that will allow you to provide valuable feedback to help improve our teaching quality.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="feature-preview mb-4">
                    <h4 class="mb-3">What to Expect:</h4>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="feature-card">
                                <div class="feature-icon">📝</div>
                                <h5>Anonymous Feedback</h5>
                                <p class="text-muted">Provide honest feedback while maintaining privacy</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="feature-card">
                                <div class="feature-icon">📊</div>
                                <h5>Rating System</h5>
                                <p class="text-muted">Rate teaching effectiveness across multiple criteria</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="feature-card">
                                <div class="feature-icon">💬</div>
                                <h5>Written Comments</h5>
                                <p class="text-muted">Share detailed suggestions and observations</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="../dashboard.php" class="btn btn-primary btn-lg me-3">
                        ← Back to Dashboard
                    </a>
                    <a href="../student/courses.php" class="btn btn-outline-primary btn-lg">
                        View My Courses
                    </a>
                </div>
            </div>
        </section>

        <!-- Timeline -->
        <section class="card">
            <h3 class="text-center mb-4">Development Timeline</h3>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-marker bg-primary"></div>
                    <div class="timeline-content">
                        <h5>Phase 1: Design & Planning</h5>
                        <p class="text-muted">Creating user-friendly evaluation forms and rating systems</p>
                        <small class="text-success">✅ Completed</small>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-marker bg-warning"></div>
                    <div class="timeline-content">
                        <h5>Phase 2: Development</h5>
                        <p class="text-muted">Building the evaluation platform and database integration</p>
                        <small class="text-warning">🔄 In Progress</small>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-marker bg-secondary"></div>
                    <div class="timeline-content">
                        <h5>Phase 3: Testing</h5>
                        <p class="text-muted">Quality assurance and user acceptance testing</p>
                        <small class="text-muted">⏳ Pending</small>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-marker bg-success"></div>
                    <div class="timeline-content">
                        <h5>Phase 4: Launch</h5>
                        <p class="text-muted">Official release and student training</p>
                        <small class="text-muted">⏳ Pending</small>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Dark Mode Toggle -->
    <div class="dark-mode-toggle" onclick="toggleDarkMode()">
        <span id="darkModeIcon">🌙</span>
    </div>

    <script>
        // Dark mode functionality
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
        
        // Load dark mode preference
        document.addEventListener('DOMContentLoaded', function() {
            const darkMode = localStorage.getItem('darkMode');
            if (darkMode === 'true') {
                document.body.classList.add('dark-mode');
                document.getElementById('darkModeIcon').textContent = '☀️';
            }
        });
    </script>

    <style>
        .error-404 {
            padding: 2rem 0;
        }
        
        .error-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }
        
        .feature-card {
            padding: 1.5rem;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            height: 100%;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            border-color: #017137;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(1, 113, 55, 0.1);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .timeline {
            position: relative;
            padding-left: 2rem;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 1rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
        }
        
        .timeline-marker {
            position: absolute;
            left: -1.5rem;
            top: 0.5rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 0 0 3px #e9ecef;
        }
        
        .timeline-content {
            padding-left: 1rem;
        }
        
        .action-buttons {
            margin-top: 2rem;
        }
        
        /* Dark mode styles for 404 page */
        .dark-mode .feature-card {
            background: #2d2d2d !important;
            border-color: #555 !important;
            color: #e0e0e0 !important;
        }
        
        .dark-mode .feature-card:hover {
            border-color: #f7e24b !important;
            box-shadow: 0 4px 12px rgba(247, 226, 75, 0.2) !important;
        }
        
        .dark-mode .timeline::before {
            background: #555 !important;
        }
        
        .dark-mode .timeline-marker {
            box-shadow: 0 0 0 3px #555 !important;
        }
    </style>
</body>
</html>
