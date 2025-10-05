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

$usersFile = __DIR__ . '/../api/data/users.json';
$allUsers = [];
if (file_exists($usersFile)) {
    $raw = @file_get_contents($usersFile);
    $allUsers = json_decode($raw ?: '[]', true) ?: [];
}

$students = [];
$studentCounter = 1;
foreach ($allUsers as $u) {
    if (($u['role'] ?? '') === 'Student') {
        $students[] = [
            'id' => $studentCounter++,
            'name' => $u['name'] ?? '',
            'email' => $u['email'] ?? '',
            'student_id' => sprintf('2024-%03d', $studentCounter - 1),
            'prelim' => 0,
            'midterm' => 0,
            'finals' => 0
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Gradebook - St. Luke's School</title>
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
        $subtitle = 'Gradebook'; $assetPrefix = '..'; include __DIR__ . '/../partials/header.php';
    ?>

    <main class="container">
        <section class="card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Grade Entry</h2>
                <div>
                    <select id="classSelector" class="form-control d-inline-block w-auto me-2" onchange="loadGrades()">
                        <option>Mathematics - Grade 10</option>
                        <option>Science - Grade 10</option>
                        <option>English - Grade 10</option>
                    </select>
                    <select id="quarterSelector" class="form-control d-inline-block w-auto me-2" onchange="loadGrades()">
                        <option value="Q1">Quarter 1</option>
                        <option value="Q2">Quarter 2</option>
                        <option value="Q3">Quarter 3</option>
                        <option value="Q4">Quarter 4</option>
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
                    <tbody id="gradesTableBody">
                        <?php foreach ($students as $student): 
                            $avg = ($student['prelim'] * 0.4) + ($student['midterm'] * 0.3) + ($student['finals'] * 0.3);
                            $remarks = $avg >= 75 ? 'Passed' : ($student['finals'] > 0 ? 'Failed' : 'Pending');
                        ?>
                        <tr data-student-id="<?= htmlspecialchars($student['student_id']) ?>" 
                            data-student-name="<?= htmlspecialchars($student['name']) ?>"
                            data-student-email="<?= htmlspecialchars($student['email']) ?>">
                            <td><?= htmlspecialchars($student['student_id']) ?></td>
                            <td><?= htmlspecialchars($student['name']) ?></td>
                            <td>
                                <input type="number" class="form-control grade-input prelim" 
                                       value="<?= $student['prelim'] ?>" min="0" max="100"
                                       onchange="updateAverage(this)">
                            </td>
                            <td>
                                <input type="number" class="form-control grade-input midterm" 
                                       value="<?= $student['midterm'] ?>" min="0" max="100"
                                       onchange="updateAverage(this)">
                            </td>
                            <td>
                                <input type="number" class="form-control grade-input finals" 
                                       value="<?= $student['finals'] ?>" min="0" max="100" 
                                       placeholder="Not yet graded"
                                       onchange="updateAverage(this)">
                            </td>
                            <td class="average-cell"><?= number_format($avg, 2) ?></td>
                            <td class="remarks-cell">
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

        function updateAverage(input) {
            const row = input.closest('tr');
            const prelim = parseFloat(row.querySelector('.prelim').value) || 0;
            const midterm = parseFloat(row.querySelector('.midterm').value) || 0;
            const finals = parseFloat(row.querySelector('.finals').value) || 0;
            
            const average = (prelim * 0.4) + (midterm * 0.3) + (finals * 0.3);
            const avgCell = row.querySelector('.average-cell');
            const remarksCell = row.querySelector('.remarks-cell');
            
            avgCell.textContent = average.toFixed(2);
            
            const passed = average >= 75;
            const pending = finals === 0;
            const status = passed ? 'Passed' : (pending ? 'Pending' : 'Failed');
            const badgeClass = passed ? 'success' : (pending ? 'warning' : 'danger');
            
            remarksCell.innerHTML = `<span class="badge bg-${badgeClass}">${status}</span>`;
        }

        async function loadGrades() {
            const classSelector = document.getElementById('classSelector');
            const quarterSelector = document.getElementById('quarterSelector');
            const selectedClass = classSelector.value;
            const selectedQuarter = quarterSelector.value;
            
            try {
                const response = await fetch(`../api/grades_api.php?action=get&class=${encodeURIComponent(selectedClass)}&quarter=${encodeURIComponent(selectedQuarter)}`);
                const result = await response.json();
                
                if (result.ok && result.grades) {
                    const gradesByStudent = {};
                    result.grades.forEach(grade => {
                        gradesByStudent[grade.student_id] = grade;
                    });
                    
                    document.querySelectorAll('#gradesTableBody tr').forEach(row => {
                        const studentId = row.dataset.studentId;
                        if (gradesByStudent[studentId]) {
                            const grade = gradesByStudent[studentId];
                            row.querySelector('.prelim').value = grade.prelim_grade || 0;
                            row.querySelector('.midterm').value = grade.midterm_grade || 0;
                            row.querySelector('.finals').value = grade.finals_grade || 0;
                            updateAverage(row.querySelector('.prelim'));
                        }
                    });
                }
            } catch (error) {
                console.error('Error loading grades:', error);
            }
        }

        async function submitGrades() {
            if (!confirm('Save all grades? This will finalize the entries.')) {
                return;
            }
            
            const classSelector = document.getElementById('classSelector');
            const quarterSelector = document.getElementById('quarterSelector');
            const selectedClass = classSelector.value;
            const selectedQuarter = quarterSelector.value;
            
            const students = [];
            document.querySelectorAll('#gradesTableBody tr').forEach(row => {
                const studentId = row.dataset.studentId;
                const studentName = row.dataset.studentName;
                const studentEmail = row.dataset.studentEmail;
                const prelim = parseFloat(row.querySelector('.prelim').value) || 0;
                const midterm = parseFloat(row.querySelector('.midterm').value) || 0;
                const finals = parseFloat(row.querySelector('.finals').value) || 0;
                
                students.push({
                    student_id: studentId,
                    student_name: studentName,
                    student_email: studentEmail,
                    prelim_grade: prelim,
                    midterm_grade: midterm,
                    finals_grade: finals
                });
            });
            
            const formData = new FormData();
            formData.append('action', 'save');
            formData.append('class', selectedClass);
            formData.append('quarter', selectedQuarter);
            formData.append('students', JSON.stringify(students));
            
            try {
                const response = await fetch('../api/grades_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.ok) {
                    showSuccess(`Grades saved successfully! (${result.saved} records saved)`);
                } else {
                    showError('Error: ' + (result.error || 'Failed to save grades'));
                }
            } catch (error) {
                showError('Error: ' + error.message);
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            loadGrades();
        });
    </script>
</body>
</html>
