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

// Sample class data - in a real system, this would come from a database
$classes = [
    [
        'id' => 1,
        'code' => 'MATH101',
        'name' => 'Algebra I',
        'schedule' => 'MWF 9:00-10:00 AM',
        'room' => 'Room 201',
        'students' => 25,
        'credits' => 3,
        'semester' => 'Fall 2024'
    ],
    [
        'id' => 2,
        'code' => 'MATH201',
        'name' => 'Calculus I',
        'schedule' => 'TTH 10:30-12:00 PM',
        'room' => 'Room 203',
        'students' => 20,
        'credits' => 4,
        'semester' => 'Fall 2024'
    ],
    [
        'id' => 3,
        'code' => 'MATH301',
        'name' => 'Statistics',
        'schedule' => 'MWF 2:00-3:00 PM',
        'room' => 'Room 205',
        'students' => 18,
        'credits' => 3,
        'semester' => 'Fall 2024'
    ]
];
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
                <button class="btn btn-success" onclick="showAddClassModal()">+ Add Class</button>
            </div>
            
            <div class="row">
                <?php foreach ($classes as $class): ?>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="class-card">
                        <div class="class-header">
                            <h3><?= htmlspecialchars($class['code']) ?></h3>
                            <span class="badge bg-primary"><?= htmlspecialchars($class['semester']) ?></span>
                        </div>
                        <h4><?= htmlspecialchars($class['name']) ?></h4>
                        <div class="class-details">
                            <p><strong>Schedule:</strong> <?= htmlspecialchars($class['schedule']) ?></p>
                            <p><strong>Room:</strong> <?= htmlspecialchars($class['room']) ?></p>
                            <p><strong>Students:</strong> <?= htmlspecialchars((string)$class['students']) ?></p>
                            <p><strong>Credits:</strong> <?= htmlspecialchars((string)$class['credits']) ?></p>
                        </div>
                        <div class="class-actions">
                            <a href="class-details.php?id=<?= $class['id'] ?>" class="btn btn-primary btn-sm">Manage</a>
                            <a href="../faculty/grades.php?class=<?= $class['id'] ?>" class="btn btn-outline-primary btn-sm">Grades</a>
                            <a href="../faculty/assignments.php?class=<?= $class['id'] ?>" class="btn btn-outline-secondary btn-sm">Assignments</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="card">
            <h2>Class Statistics</h2>
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3><?= count($classes) ?></h3>
                        <p>Total Classes</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3><?= array_sum(array_column($classes, 'students')) ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3><?= array_sum(array_column($classes, 'credits')) ?></h3>
                        <p>Teaching Load</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3>92%</h3>
                        <p>Avg Attendance</p>
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
        .class-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 8px;
            padding: 1.5rem;
            height: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .class-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .class-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .class-header h3 {
            margin: 0;
            color: var(--color-accent);
            font-size: 1.1rem;
        }
        
        .class-card h4 {
            color: var(--color-text);
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        
        .class-details p {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .class-actions {
            margin-top: 1rem;
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .stat-card {
            text-align: center;
            padding: 1.5rem;
            background: var(--color-surface);
            border-radius: 8px;
            border: 1px solid var(--color-border);
        }
        
        .stat-card h3 {
            font-size: 2rem;
            color: var(--color-accent);
            margin: 0;
        }
        
        .stat-card p {
            margin: 0.5rem 0 0 0;
            color: var(--color-muted);
        }
    </style>

    <script>
        function showAddClassModal() {
            showInfo('Add class functionality would be implemented here');
        }
    </script>
</body>
</html>
