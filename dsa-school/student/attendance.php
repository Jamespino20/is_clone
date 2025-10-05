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

// Fetch attendance records from API
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

// Group attendance by section (class)
$attendanceBySectionArray = [];
foreach ($attendanceRecords as $record) {
    $section = $record['section'] ?? 'Unknown';
    if (!isset($attendanceBySectionArray[$section])) {
        $attendanceBySectionArray[$section] = [];
    }
    $attendanceBySectionArray[$section][] = $record;
}

// Calculate attendance statistics
$totalClasses = count($attendanceRecords);
$totalPresent = 0;
$totalLate = 0;
$totalAbsent = 0;

foreach ($attendanceRecords as $record) {
    $status = strtolower($record['status'] ?? '');
    switch ($status) {
        case 'present':
            $totalPresent++;
            break;
        case 'late':
            $totalLate++;
            break;
        case 'absent':
            $totalAbsent++;
            break;
    }
}

$attendanceRate = $totalClasses > 0 ? (($totalPresent + $totalLate) / $totalClasses) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Attendance - St. Luke's School</title>
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
        $subtitle = 'My Attendance'; $assetPrefix = '..'; include __DIR__ . '/../partials/header.php';
    ?>

    <main class="container">
        <section class="card">
            <h2>Attendance Summary</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3><?= number_format($attendanceRate, 1) ?>%</h3>
                        <p>Overall Attendance</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3><?= $totalPresent ?></h3>
                        <p>Present</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">⏰</div>
                    <div class="stat-content">
                        <h3><?= $totalLate ?></h3>
                        <p>Late</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">❌</div>
                    <div class="stat-content">
                        <h3><?= $totalAbsent ?></h3>
                        <p>Absent</p>
                    </div>
                </div>
            </div>
        </section>

        <?php if (empty($attendanceRecords)): ?>
            <section class="card">
                <div class="alert alert-info">
                    <p class="mb-0">No attendance records found. Your attendance will appear here once recorded by faculty.</p>
                </div>
            </section>
        <?php else: ?>
            <?php foreach ($attendanceBySectionArray as $section => $records): ?>
            <section class="card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2><?= htmlspecialchars($section) ?></h2>
                        <p class="text-muted mb-0">
                            <strong>Total Records:</strong> <?= count($records) ?>
                        </p>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Grade Level</th>
                                <th>Status</th>
                                <th>Remarks</th>
                                <th>Recorded By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?= date('M j, Y', strtotime($record['date'] ?? 'now')) ?></td>
                                <td><?= htmlspecialchars($record['grade_level'] ?? 'N/A') ?></td>
                                <td>
                                    <?php
                                    $status = ucfirst(strtolower($record['status'] ?? 'Unknown'));
                                    $badgeClass = match(strtolower($record['status'] ?? '')) {
                                        'present' => 'bg-success',
                                        'late' => 'bg-warning',
                                        'absent' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= $status ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($record['remarks'])): ?>
                                        <?= htmlspecialchars($record['remarks']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><small><?= htmlspecialchars($record['recorded_by'] ?? 'N/A') ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php endforeach; ?>
        <?php endif; ?>
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-item {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 8px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .stat-icon {
            font-size: 2rem;
        }
        
        .stat-content h3 {
            font-size: 2rem;
            margin: 0;
            color: var(--color-accent);
        }
        
        .stat-content p {
            margin: 0;
            color: var(--color-muted);
            font-size: 0.9rem;
        }
    </style>
</body>
</html>
