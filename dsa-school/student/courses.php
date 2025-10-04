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

// Sample course data - in a real system, this would come from a database
$courses = [
    [
        'id' => 1,
        'code' => 'MATH101',
        'name' => 'Algebra I',
        'instructor' => 'Dr. Maria Santos',
        'schedule' => 'MWF 9:00-10:00 AM',
        'room' => 'Room 201',
        'credits' => 3,
        'status' => 'Enrolled'
    ],
    [
        'id' => 2,
        'code' => 'ENG101',
        'name' => 'English Composition',
        'instructor' => 'Prof. John Cruz',
        'schedule' => 'TTH 10:30-12:00 PM',
        'room' => 'Room 105',
        'credits' => 3,
        'status' => 'Enrolled'
    ],
    [
        'id' => 3,
        'code' => 'SCI101',
        'name' => 'General Science',
        'instructor' => 'Dr. Ana Reyes',
        'schedule' => 'MWF 1:00-2:00 PM',
        'room' => 'Lab 301',
        'credits' => 4,
        'status' => 'Enrolled'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Courses - St. Luke's School</title>
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
                <span class="topbar-subtitle">My Courses</span>
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
        <section class="card">
            <h2>Enrolled Courses</h2>
            <p class="text-muted">Here are your current courses for this semester.</p>
            
            <div class="row">
                <?php foreach ($courses as $course): ?>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="course-card">
                        <div class="course-header">
                            <h3><?= htmlspecialchars($course['code']) ?></h3>
                            <span class="badge bg-success"><?= htmlspecialchars($course['status']) ?></span>
                        </div>
                        <h4><?= htmlspecialchars($course['name']) ?></h4>
                        <div class="course-details">
                            <p><strong>Instructor:</strong> <?= htmlspecialchars($course['instructor']) ?></p>
                            <p><strong>Schedule:</strong> <?= htmlspecialchars($course['schedule']) ?></p>
                            <p><strong>Room:</strong> <?= htmlspecialchars($course['room']) ?></p>
                            <p><strong>Credits:</strong> <?= htmlspecialchars((string)$course['credits']) ?></p>
                        </div>
                        <div class="course-actions">
                            <a href="course-details.php?id=<?= $course['id'] ?>" class="btn btn-primary btn-sm">View Details</a>
                            <a href="../student/grades.php?course=<?= $course['id'] ?>" class="btn btn-outline-primary btn-sm">View Grades</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="card">
            <h2>Course Statistics</h2>
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3><?= count($courses) ?></h3>
                        <p>Total Courses</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3><?= array_sum(array_column($courses, 'credits')) ?></h3>
                        <p>Total Credits</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3>3.2</h3>
                        <p>Current GPA</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3>85%</h3>
                        <p>Attendance</p>
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
        .course-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1.5rem;
            height: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .course-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .course-header h3 {
            margin: 0;
            color: #017137;
            font-size: 1.1rem;
        }
        
        .course-card h4 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        
        .course-details p {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .course-actions {
            margin-top: 1rem;
            display: flex;
            gap: 0.5rem;
        }
        
        .stat-card {
            text-align: center;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            color: #017137;
            margin: 0;
        }
        
        .stat-card p {
            margin: 0.5rem 0 0 0;
            color: #6c757d;
        }
    </style>
</body>
</html>
