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
    ['id' => 1, 'name' => 'Juan Dela Cruz', 'student_id' => '2024-001', 'prelim' => 85, 'midterm' => 88, 'finals' => 0],
    ['id' => 2, 'name' => 'Maria Santos', 'student_id' => '2024-002', 'prelim' => 92, 'midterm' => 95, 'finals' => 0],
    ['id' => 3, 'name' => 'Pedro Rodriguez', 'student_id' => '2024-003', 'prelim' => 78, 'midterm' => 75, 'finals' => 0]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Gradebook - St. Luke's School</title>
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
        $subtitle = 'Gradebook'; $assetPrefix = '..'; include __DIR__ . '/../partials/header.php';
    ?>

    <main class="container">
        <section class="card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Grade Entry</h2>
                <div>
                    <select class="form-control d-inline-block w-auto me-2">
                        <option>Mathematics - Grade 10</option>
                        <option>Science - Grade 10</option>
                        <option>English - Grade 10</option>
                    </select>
                    <button class="btn btn-success" onclick="submitGrades()">💾 Save All Grades</button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Prelim (40%)</th>
                            <th>Midterm (30%)</th>
                            <th>Finals (30%)</th>
                            <th>Average</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): 
                            $avg = ($student['prelim'] * 0.4) + ($student['midterm'] * 0.3) + ($student['finals'] * 0.3);
                            $remarks = $avg >= 75 ? 'Passed' : ($student['finals'] > 0 ? 'Failed' : 'Pending');
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($student['student_id']) ?></td>
                            <td><?= htmlspecialchars($student['name']) ?></td>
                            <td>
                                <input type="number" class="form-control" value="<?= $student['prelim'] ?>" min="0" max="100">
                            </td>
                            <td>
                                <input type="number" class="form-control" value="<?= $student['midterm'] ?>" min="0" max="100">
                            </td>
                            <td>
                                <input type="number" class="form-control" value="<?= $student['finals'] ?>" min="0" max="100" placeholder="Not yet graded">
                            </td>
                            <td><?= number_format($avg, 2) ?></td>
                            <td>
                                <span class="badge bg-<?= $avg >= 75 ? 'success' : ($student['finals'] > 0 ? 'danger' : 'warning') ?>">
                                    <?= $remarks ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <h2>Grade Statistics</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3><?= count($students) ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3><?= round((array_sum(array_column($students, 'prelim')) / count($students)), 1) ?></h3>
                        <p>Class Average</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📈</div>
                    <div class="stat-content">
                        <h3><?= max(array_column($students, 'prelim')) ?></h3>
                        <p>Highest Grade</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📉</div>
                    <div class="stat-content">
                        <h3><?= min(array_column($students, 'prelim')) ?></h3>
                        <p>Lowest Grade</p>
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

        function submitGrades() {
            if (confirm('Save all grades? This will finalize the entries.')) {
                alert('Grades saved successfully!');
            }
        }
    </script>
</body>
</html>
