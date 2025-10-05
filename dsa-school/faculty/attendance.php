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

$all_users = read_users();
$students = array_values(array_filter($all_users, fn($u) => $u['role'] === 'Student'));
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
        <div id="messageContainer"></div>
        
        <section class="card">
            <h2>Mark Attendance</h2>
            <form>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input type="date" id="attendanceDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Class</label>
                        <select id="attendanceClass" class="form-control" required>
                            <option>Mathematics - Grade 10</option>
                            <option>Science - Grade 10</option>
                            <option>English - Grade 10</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Period</label>
                        <select id="attendancePeriod" class="form-control" required>
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
                    <tbody id="studentTableBody">
                        <?php foreach ($students as $index => $student): ?>
                        <tr data-student-id="<?= htmlspecialchars($student['email']) ?>">
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($student['email']) ?></td>
                            <td><?= htmlspecialchars($student['name']) ?></td>
                            <td>
                                <select class="form-control form-control-sm student-status">
                                    <option value="present">Present</option>
                                    <option value="absent">Absent</option>
                                    <option value="late">Late</option>
                                    <option value="excused">Excused</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm student-remarks" placeholder="Optional remarks">
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
                        <h3 id="summaryPresent">0</h3>
                        <p>Present</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">❌</div>
                    <div class="stat-content">
                        <h3 id="summaryAbsent">0</h3>
                        <p>Absent</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3 id="summaryRate">0%</h3>
                        <p>Attendance Rate</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3 id="summaryTotal"><?= count($students) ?></h3>
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
            
            loadAttendance();
            
            document.querySelectorAll('.student-status').forEach(select => {
                select.addEventListener('change', updateSummary);
            });
            
            document.getElementById('attendanceDate').addEventListener('change', loadAttendance);
            document.getElementById('attendanceClass').addEventListener('change', loadAttendance);
        });

        function showMessage(message, isError = false) {
            const container = document.getElementById('messageContainer');
            container.innerHTML = `
                <div class="alert alert-${isError ? 'danger' : 'success'} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
        }

        function extractGradeFromClass(className) {
            const match = className.match(/Grade\s+(\d+)/i);
            return match ? match[1] : '10';
        }

        async function loadAttendance() {
            const date = document.getElementById('attendanceDate').value;
            const className = document.getElementById('attendanceClass').value;
            const grade = extractGradeFromClass(className);
            
            try {
                const response = await fetch(`../api/staff_attendance.php?action=list&date=${date}&grade=${grade}&section=${encodeURIComponent(className)}`);
                const data = await response.json();
                
                if (data.ok && data.items && data.items.length > 0) {
                    data.items.forEach(record => {
                        const row = document.querySelector(`tr[data-student-id="${record.student_id}"]`);
                        if (row) {
                            const statusSelect = row.querySelector('.student-status');
                            const remarksInput = row.querySelector('.student-remarks');
                            if (statusSelect) statusSelect.value = record.status || 'present';
                            if (remarksInput) remarksInput.value = record.remarks || '';
                        }
                    });
                    updateSummary();
                } else {
                    document.querySelectorAll('.student-status').forEach(select => select.value = 'present');
                    document.querySelectorAll('.student-remarks').forEach(input => input.value = '');
                    updateSummary();
                }
            } catch (error) {
                console.error('Error loading attendance:', error);
                updateSummary();
            }
        }

        function updateSummary() {
            const rows = document.querySelectorAll('#studentTableBody tr');
            let present = 0, absent = 0, late = 0, excused = 0;
            
            rows.forEach(row => {
                const status = row.querySelector('.student-status')?.value || 'present';
                switch(status) {
                    case 'present': present++; break;
                    case 'absent': absent++; break;
                    case 'late': late++; break;
                    case 'excused': excused++; break;
                }
            });
            
            const total = rows.length;
            const rate = total > 0 ? Math.round(((present + late + excused) / total) * 100) : 0;
            
            document.getElementById('summaryPresent').textContent = present;
            document.getElementById('summaryAbsent').textContent = absent;
            document.getElementById('summaryRate').textContent = rate + '%';
            document.getElementById('summaryTotal').textContent = total;
        }

        function markAll(status) {
            document.querySelectorAll('.student-status').forEach(select => {
                select.value = status;
            });
            updateSummary();
        }

        async function saveAttendance() {
            if (!confirm('Save attendance records?')) return;
            
            const date = document.getElementById('attendanceDate').value;
            const className = document.getElementById('attendanceClass').value;
            const grade = extractGradeFromClass(className);
            
            const students = [];
            document.querySelectorAll('#studentTableBody tr').forEach(row => {
                const studentId = row.getAttribute('data-student-id');
                const status = row.querySelector('.student-status')?.value || 'present';
                const remarks = row.querySelector('.student-remarks')?.value || '';
                
                students.push({
                    student_id: studentId,
                    status: status,
                    remarks: remarks
                });
            });
            
            const payload = {
                date: date,
                grade_level: grade,
                section: className,
                students: students
            };
            
            try {
                const formData = new FormData();
                formData.append('action', 'save');
                formData.append('payload', JSON.stringify(payload));
                
                const response = await fetch('../api/staff_attendance.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.ok) {
                    showMessage('✅ Attendance saved successfully!');
                } else {
                    showMessage('❌ Error: ' + (data.error || 'Failed to save attendance'), true);
                }
            } catch (error) {
                console.error('Error saving attendance:', error);
                showMessage('❌ Error: Failed to save attendance', true);
            }
        }
    </script>
</body>
</html>
