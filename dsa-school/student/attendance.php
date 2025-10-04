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

// Sample attendance data - in a real system, this would come from a database
$attendance = [
    'MATH101' => [
        'subject' => 'Mathematics',
        'instructor' => 'Dr. Maria Santos',
        'schedule' => 'MWF 9:00-10:00 AM',
        'records' => [
            ['date' => '2024-01-15', 'time' => '09:05', 'status' => 'Late', 'minutes_late' => 5],
            ['date' => '2024-01-17', 'time' => '08:55', 'status' => 'Present', 'minutes_late' => 0],
            ['date' => '2024-01-19', 'time' => '09:15', 'status' => 'Late', 'minutes_late' => 15],
            ['date' => '2024-01-22', 'time' => '09:00', 'status' => 'Present', 'minutes_late' => 0],
            ['date' => '2024-01-24', 'time' => '09:20', 'status' => 'Late', 'minutes_late' => 20],
            ['date' => '2024-01-26', 'time' => null, 'status' => 'Absent', 'minutes_late' => null],
        ]
    ],
    'ENG101' => [
        'subject' => 'English',
        'instructor' => 'Prof. John Cruz',
        'schedule' => 'TTH 10:30-12:00 PM',
        'records' => [
            ['date' => '2024-01-16', 'time' => '10:30', 'status' => 'Present', 'minutes_late' => 0],
            ['date' => '2024-01-18', 'time' => '10:35', 'status' => 'Late', 'minutes_late' => 5],
            ['date' => '2024-01-23', 'time' => '10:30', 'status' => 'Present', 'minutes_late' => 0],
            ['date' => '2024-01-25', 'time' => '10:45', 'status' => 'Late', 'minutes_late' => 15],
        ]
    ]
];

// Calculate attendance statistics
$totalClasses = 0;
$totalPresent = 0;
$totalLate = 0;
$totalAbsent = 0;

foreach ($attendance as $subjectData) {
    foreach ($subjectData['records'] as $record) {
        $totalClasses++;
        switch ($record['status']) {
            case 'Present':
                $totalPresent++;
                break;
            case 'Late':
                $totalLate++;
                break;
            case 'Absent':
                $totalAbsent++;
                break;
        }
    }
}

$attendanceRate = $totalClasses > 0 ? (($totalPresent + $totalLate) / $totalClasses) * 100 : 0;

// Handle attendance marking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_attendance') {
    $subject = $_POST['subject'] ?? '';
    $time = $_POST['time'] ?? '';
    $status = $_POST['status'] ?? '';
    $minutesLate = (int)($_POST['minutes_late'] ?? 0);
    
    if ($subject && $time && $status) {
        // In a real system, this would save to database
        // For now, we'll just return success
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Attendance recorded successfully']);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Missing required data']);
        exit;
    }
}
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
    <header class="topbar">
        <div class="topbar-left">
            <img src="../assets/img/school-logo.png" alt="School Logo" class="topbar-logo">
            <div class="topbar-title">
                <h1>My Attendance</h1>
                <span class="topbar-subtitle">Student Portal</span>
            </div>
        </div>
        <div class="topbar-right">
            <div class="user-info">
                <span class="user-name"><?= htmlspecialchars($user['name']) ?></span>
                <span class="user-role"><?= get_role_display_name($user['role']) ?></span>
            </div>
            <nav>
                <a href="../dashboard.php" class="nav-link">← Dashboard</a>
                <a href="../api/logout.php" class="nav-link logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <!-- Attendance Summary -->
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

        <!-- Attendance by Subject -->
        <?php foreach ($attendance as $subjectCode => $subjectData): ?>
        <section class="card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2><?= htmlspecialchars($subjectData['subject']) ?></h2>
                    <p class="text-muted mb-0">
                        <strong>Instructor:</strong> <?= htmlspecialchars($subjectData['instructor']) ?> | 
                        <strong>Schedule:</strong> <?= htmlspecialchars($subjectData['schedule']) ?>
                    </p>
                </div>
                <button class="btn btn-outline-primary" onclick="markAttendance('<?= $subjectCode ?>')">
                    📝 Mark Attendance
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Minutes Late</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjectData['records'] as $record): ?>
                        <tr>
                            <td><?= date('M j, Y', strtotime($record['date'])) ?></td>
                            <td>
                                <?php if ($record['time']): ?>
                                    <?= date('g:i A', strtotime($record['time'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $badgeClass = match($record['status']) {
                                    'Present' => 'bg-success',
                                    'Late' => 'bg-warning',
                                    'Absent' => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $record['status'] ?></span>
                            </td>
                            <td>
                                <?php if ($record['minutes_late'] !== null): ?>
                                    <?php if ($record['minutes_late'] > 0): ?>
                                        <span class="text-warning"><?= $record['minutes_late'] ?> min</span>
                                    <?php else: ?>
                                        <span class="text-success">On time</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($record['status'] === 'Late' && $record['minutes_late'] > 15): ?>
                                    <span class="text-danger">Excessive tardiness</span>
                                <?php elseif ($record['status'] === 'Late' && $record['minutes_late'] <= 5): ?>
                                    <span class="text-info">Minor delay</span>
                                <?php elseif ($record['status'] === 'Absent'): ?>
                                    <span class="text-danger">No excuse provided</span>
                                <?php else: ?>
                                    <span class="text-success">Good attendance</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endforeach; ?>

        <!-- Attendance Policy -->
        <section class="card">
            <h2>Attendance Policy</h2>
            <div class="row">
                <div class="col-md-6">
                    <h4>Time Ranges</h4>
                    <ul class="list-unstyled">
                        <li><span class="badge bg-success me-2">Present</span> On time or within 5 minutes</li>
                        <li><span class="badge bg-warning me-2">Late</span> 6-15 minutes after class start</li>
                        <li><span class="badge bg-danger me-2">Absent</span> More than 15 minutes late or no show</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h4>Consequences</h4>
                    <ul class="list-unstyled">
                        <li>• 3 consecutive absences = Parent notification</li>
                        <li>• 5 total absences = Academic warning</li>
                        <li>• 10 total absences = Possible course failure</li>
                        <li>• Excessive tardiness affects participation grade</li>
                    </ul>
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

        function markAttendance(subjectCode) {
            const now = new Date();
            const currentTime = now.toLocaleTimeString('en-US', { 
                hour12: true, 
                hour: 'numeric', 
                minute: '2-digit' 
            });
            
            // Check if it's within class time (basic validation)
            const currentHour = now.getHours();
            const currentMinute = now.getMinutes();
            
            // Define class schedules (in 24-hour format)
            const classSchedules = {
                'MATH101': { start: 9, end: 10, days: [1, 3, 5] }, // MWF 9:00-10:00 AM
                'ENG101': { start: 10.5, end: 12, days: [2, 4] }   // TTH 10:30-12:00 PM
            };
            
            const schedule = classSchedules[subjectCode];
            const currentDay = now.getDay(); // 0 = Sunday, 1 = Monday, etc.
            
            if (!schedule) {
                alert('Invalid subject code.');
                return;
            }
            
            // Check if it's the right day
            if (!schedule.days.includes(currentDay)) {
                alert(`Today is not a scheduled day for ${subjectCode}.`);
                return;
            }
            
            // Check if it's within class time (with 15-minute grace period)
            const classStart = schedule.start * 60; // Convert to minutes
            const classEnd = schedule.end * 60;
            const currentMinutes = currentHour * 60 + currentMinute;
            const gracePeriod = 15; // 15 minutes grace period
            
            if (currentMinutes < classStart - gracePeriod || currentMinutes > classEnd + gracePeriod) {
                alert(`It's not the right time for ${subjectCode}. Class is from ${schedule.start}:00 to ${schedule.end}:00.`);
                return;
            }
            
            // Calculate if student is late
            const isLate = currentMinutes > classStart;
            const minutesLate = isLate ? currentMinutes - classStart : 0;
            
            let status = 'Present';
            if (minutesLate > 15) {
                status = 'Absent';
            } else if (minutesLate > 0) {
                status = 'Late';
            }
            
            const message = `Mark your attendance for ${subjectCode}?\n\n` +
                          `Time: ${currentTime}\n` +
                          `Status: ${status}\n` +
                          (minutesLate > 0 ? `Minutes Late: ${minutesLate}\n` : '') +
                          `\nThis will be recorded in your attendance record.`;
            
            if (confirm(message)) {
                // Simulate API call
                fetch('attendance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=mark_attendance&subject=${subjectCode}&time=${currentTime}&status=${status}&minutes_late=${minutesLate}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`✅ Attendance marked successfully!\n\nSubject: ${subjectCode}\nTime: ${currentTime}\nStatus: ${status}`);
                        // Reload the page to show updated attendance
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        alert('❌ Failed to mark attendance. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('❌ An error occurred. Please try again.');
                });
            }
        }
    </script>
</body>
</html>
