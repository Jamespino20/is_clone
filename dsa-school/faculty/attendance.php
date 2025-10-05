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

$students = [
    ['id' => 1, 'name' => 'Juan Dela Cruz', 'student_id' => '2024-001', 'status' => 'present'],
    ['id' => 2, 'name' => 'Maria Santos', 'student_id' => '2024-002', 'status' => 'present'],
    ['id' => 3, 'name' => 'Pedro Rodriguez', 'student_id' => '2024-003', 'status' => 'absent']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Attendance - St. Luke's School</title>
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
        $subtitle = 'Class Attendance'; $assetPrefix = '..'; include __DIR__ . '/../partials/header.php';
    ?>

    <main class="container">
        <section class="card">
            <h2>Mark Attendance</h2>
            <form>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Class</label>
                        <select class="form-control" required>
                            <option>Mathematics - Grade 10</option>
                            <option>Science - Grade 10</option>
                            <option>English - Grade 10</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Period</label>
                        <select class="form-control" required>
                            <option value="1">Period 1 (7:00 AM)</option>
                            <option value="2">Period 2 (8:00 AM)</option>
                            <option value="3">Period 3 (9:00 AM)</option>
                            <option value="4">Period 4 (10:00 AM)</option>
                        </select>
                    </div>
                </div>
            </form>
        </section>

        <section class="card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Student List</h2>
                <div>
                    <button class="btn btn-success btn-sm" onclick="markAll('present')">✅ Mark All Present</button>
                    <button class="btn btn-danger btn-sm" onclick="markAll('absent')">❌ Mark All Absent</button>
                    <button class="btn btn-primary" onclick="saveAttendance()">💾 Save Attendance</button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $index => $student): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($student['student_id']) ?></td>
                            <td><?= htmlspecialchars($student['name']) ?></td>
                            <td>
                                <select class="form-control form-control-sm" id="status_<?= $student['id'] ?>">
                                    <option value="present" <?= $student['status'] === 'present' ? 'selected' : '' ?>>Present</option>
                                    <option value="absent" <?= $student['status'] === 'absent' ? 'selected' : '' ?>>Absent</option>
                                    <option value="late">Late</option>
                                    <option value="excused">Excused</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" placeholder="Optional remarks">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <h2>Attendance Summary</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3><?= count(array_filter($students, fn($s) => $s['status'] === 'present')) ?></h3>
                        <p>Present Today</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">❌</div>
                    <div class="stat-content">
                        <h3><?= count(array_filter($students, fn($s) => $s['status'] === 'absent')) ?></h3>
                        <p>Absent Today</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3><?= round((count(array_filter($students, fn($s) => $s['status'] === 'present')) / count($students)) * 100) ?>%</h3>
                        <p>Attendance Rate</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3><?= count($students) ?></h3>
                        <p>Total Students</p>
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

        function markAll(status) {
            const selects = document.querySelectorAll('select[id^="status_"]');
            selects.forEach(select => select.value = status);
        }

        function saveAttendance() {
            if (confirm('Save attendance records?')) {
                alert('Attendance saved successfully!');
            }
        }
    </script>
</body>
</html>
