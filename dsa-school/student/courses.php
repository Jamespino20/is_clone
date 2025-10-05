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

// Fetch student profile data from API
$profileUrl = 'http://localhost:5000/api/student_data.php?action=profile';
$profileContext = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Cookie: ' . session_name() . '=' . session_id()
    ]
]);
$profileResponse = @file_get_contents($profileUrl, false, $profileContext);
$profileData = json_decode($profileResponse ?: '{}', true);
$studentProfile = $profileData['student'] ?? [];

// Fetch courses data from API
$coursesUrl = 'http://localhost:5000/api/student_data.php?action=courses';
$coursesContext = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Cookie: ' . session_name() . '=' . session_id()
    ]
]);
$coursesResponse = @file_get_contents($coursesUrl, false, $coursesContext);
$coursesData = json_decode($coursesResponse ?: '{}', true);
$courses = $coursesData['courses'] ?? [];

// Fetch grades to calculate GPA
$gradesUrl = 'http://localhost:5000/api/student_data.php?action=grades';
$gradesContext = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Cookie: ' . session_name() . '=' . session_id()
    ]
]);
$gradesResponse = @file_get_contents($gradesUrl, false, $gradesContext);
$gradesData = json_decode($gradesResponse ?: '{}', true);
$grades = $gradesData['grades'] ?? [];

// Calculate GPA from actual grades
$totalAverage = 0;
$gradeCount = 0;
foreach ($grades as $grade) {
    if (isset($grade['average']) && $grade['average'] > 0) {
        $totalAverage += $grade['average'];
        $gradeCount++;
    }
}
$currentGPA = $gradeCount > 0 ? round($totalAverage / $gradeCount, 2) : 0;

// Fetch attendance to calculate attendance rate
$attendanceUrl = 'http://localhost:5000/api/student_data.php?action=attendance';
$attendanceContext = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Cookie: ' . session_name() . '=' . session_id()
    ]
]);
$attendanceResponse = @file_get_contents($attendanceUrl, false, $attendanceContext);
$attendanceData = json_decode($attendanceResponse ?: '{}', true);
$attendanceRecords = $attendanceData['attendance'] ?? [];

// Calculate attendance statistics
$totalClasses = count($attendanceRecords);
$presentCount = 0;
$lateCount = 0;
foreach ($attendanceRecords as $record) {
    $status = strtolower($record['status'] ?? '');
    if ($status === 'present') $presentCount++;
    if ($status === 'late') $lateCount++;
}
$attendanceRate = $totalClasses > 0 ? round((($presentCount + $lateCount) / $totalClasses) * 100, 1) : 0;
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
    <?php
        require_once __DIR__ . '/../api/data_structures.php';
        $dsManager = DataStructuresManager::getInstance();
        $userRole = get_role_display_name($user['role']);
        $userNotifications = array_filter($dsManager->getNotificationQueue()->getAll(), fn($n) => $n['user_email'] === $email);
        $unreadNotifications = array_filter($userNotifications, fn($n) => !$n['read']);
        $subtitle = 'My Courses'; $assetPrefix = '..'; include __DIR__ . '/../partials/header.php';
    ?>

    <main class="container">
        <section class="card">
            <h2>Student Profile</h2>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Name:</strong> <?= htmlspecialchars($studentProfile['name'] ?? $user['name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($studentProfile['email'] ?? $email) ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Grade Level:</strong> <?= htmlspecialchars((string)($studentProfile['grade_level'] ?? 'Not Assigned')) ?></p>
                    <p><strong>Section:</strong> <?= htmlspecialchars($studentProfile['section'] ?? 'Not Assigned') ?></p>
                </div>
            </div>
        </section>

        <section class="card">
            <h2>Enrolled Courses</h2>
            <?php if (empty($courses)): ?>
                <div class="alert alert-info">
                    <p class="mb-0">No courses found. You may not be enrolled in any classes yet or no grades have been recorded.</p>
                </div>
            <?php else: ?>
                <p class="text-muted">Here are your current courses based on recorded grades.</p>
                
                <div class="row">
                    <?php foreach ($courses as $course): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="course-card">
                            <div class="course-header">
                                <h3><?= htmlspecialchars($course['class']) ?></h3>
                                <span class="badge bg-success">Enrolled</span>
                            </div>
                            <div class="course-details">
                                <p><strong>Faculty:</strong> <?= htmlspecialchars($course['faculty_email']) ?></p>
                            </div>
                            <div class="course-actions">
                                <a href="../student/grades.php" class="btn btn-primary btn-sm">View Grades</a>
                                <a href="../student/attendance.php" class="btn btn-outline-primary btn-sm">View Attendance</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2>Academic Statistics</h2>
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3><?= count($courses) ?></h3>
                        <p>Total Courses</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3><?= $currentGPA ?></h3>
                        <p>Current GPA</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3><?= $attendanceRate ?>%</h3>
                        <p>Attendance</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3><?= $gradeCount ?></h3>
                        <p>Graded Quarters</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div class="dark-mode-toggle" onclick="toggleDarkMode()">
        <span id="darkModeIcon">🌙</span>
    </div>

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
    </script>

    <style>
        .course-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
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
            color: var(--color-accent);
            font-size: 1.1rem;
        }
        
        .course-card h4 {
            color: var(--color-text);
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
</body>
</html>
