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
if (!$user || !has_permission(get_role_display_name($user['role']), 'Staff')) {
    header('Location: ../dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Attendance Management - St. Luke's School</title>
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
        $subtitle = 'Attendance Management'; $assetPrefix = '..'; include __DIR__ . '/../partials/header.php';
    ?>

    <main class="container">
        <section class="card">
            <h2>Mark Attendance</h2>
            <form id="attendanceForm">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Date</label>
                        <input type="date" id="attDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Grade Level</label>
                        <select id="attGrade" class="form-control" required>
                            <option value="">Select Grade</option>
                            <option value="Kinder">Kinder</option>
                            <option value="Grade 1">Grade 1</option>
                            <option value="Grade 2">Grade 2</option>
                            <option value="Grade 3">Grade 3</option>
                            <option value="Grade 4">Grade 4</option>
                            <option value="Grade 5">Grade 5</option>
                            <option value="Grade 6">Grade 6</option>
                            <option value="Grade 7">Grade 7</option>
                            <option value="Grade 8">Grade 8</option>
                            <option value="Grade 9">Grade 9</option>
                            <option value="Grade 10">Grade 10</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Section</label>
                        <select id="attSection" class="form-control">
                            <option value="">Select Section</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Load Students</button>
            </form>
        </section>

        <!-- Attendance Marking Interface -->
        <section class="card" id="attendanceSection" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Student Attendance</h2>
                <button class="btn btn-success" onclick="saveAttendance()">💾 Save Attendance</button>
            </div>
            <div class="table-responsive">
                <table class="table table-striped" id="attendanceTable">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Present</th>
                            <th>Late</th>
                            <th>Absent</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceTableBody">
                        <!-- Student rows will be populated here -->
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
                        <h3>92.5%</h3>
                        <p>Overall Attendance</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3>245</h3>
                        <p>Total Students</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📅</div>
                    <div class="stat-content">
                        <h3><?= date('F j, Y') ?></h3>
                        <p>Today's Date</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">⏰</div>
                    <div class="stat-content">
                        <h3><?= date('g:i A') ?></h3>
                        <p>Current Time</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="card">
            <h2>Attendance Reports</h2>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <button class="btn btn-outline-primary w-100" onclick="generateReport('daily')">📊 Daily Report</button>
                </div>
                <div class="col-md-6 mb-3">
                    <button class="btn btn-outline-primary w-100" onclick="generateReport('weekly')">📊 Weekly Report</button>
                </div>
                <div class="col-md-6 mb-3">
                    <button class="btn btn-outline-primary w-100" onclick="generateReport('monthly')">📊 Monthly Report</button>
                </div>
                <div class="col-md-6 mb-3">
                    <button class="btn btn-outline-primary w-100" onclick="generateReport('student')">📊 Student Report</button>
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

        document.getElementById('attendanceForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const date = document.getElementById('attDate').value;
            const grade = document.getElementById('attGrade').value;
            const section = document.getElementById('attSection').value;

            if (!grade || !section) {
                showWarning('Please select both grade level and section');
                return;
            }

            // Load students for the selected grade and section
            await loadStudentsForAttendance(date, grade, section);
        });

        async function loadStudentsForAttendance(date, grade, section) {
            try {
                // Get students from the students API
                const response = await fetch('../api/students_api.php?action=list', {
                    credentials: 'same-origin'
                });
                const data = await response.json();

                if (!data.ok) {
                    showError('Failed to load students: ' + (data.error || 'Unknown error'));
                    return;
                }

                console.log('Students loaded:', data.items.length);
                console.log('Sample student:', data.items[0]);

                // Filter students by grade and section
                const filteredStudents = data.items.filter(student =>
                    student.grade_level === grade && student.section === section
                );

                console.log(`Filtered students for ${grade} ${section}:`, filteredStudents.length);

                // Load existing attendance records for this date/grade/section
                const attResponse = await fetch(`../api/staff_attendance.php?action=list&date=${date}&grade=${encodeURIComponent(grade)}&section=${encodeURIComponent(section)}`, {
                    credentials: 'same-origin'
                });
                const attData = await attResponse.json();

                const existingRecords = attData.ok ? attData.items : [];

                renderAttendanceTable(filteredStudents, existingRecords);
                document.getElementById('attendanceSection').style.display = 'block';

            } catch (error) {
                showError('Error loading students: ' + error.message);
                console.error('Error in loadStudentsForAttendance:', error);
            }
        }

        function renderAttendanceTable(students, existingRecords = []) {
            const tbody = document.getElementById('attendanceTableBody');
            
            // Create a map of existing records by student_id for quick lookup
            const recordsMap = {};
            existingRecords.forEach(record => {
                recordsMap[record.student_id] = record;
            });
            
            tbody.innerHTML = students.map(student => {
                const existing = recordsMap[student.student_id];
                const status = existing ? existing.status : 'present';
                const remarks = existing ? (existing.remarks || '') : '';
                
                return `
                    <tr data-student-id="${student.student_id}">
                        <td>${student.student_id}</td>
                        <td>${student.name}</td>
                        <td><input type="radio" name="status_${student.student_id}" value="present" ${status === 'present' ? 'checked' : ''}></td>
                        <td><input type="radio" name="status_${student.student_id}" value="late" ${status === 'late' ? 'checked' : ''}></td>
                        <td><input type="radio" name="status_${student.student_id}" value="absent" ${status === 'absent' ? 'checked' : ''}></td>
                        <td><input type="text" class="form-control" placeholder="Optional remarks" value="${remarks}"></td>
                    </tr>
                `;
            }).join('');
        }

        async function saveAttendance() {
            const date = document.getElementById('attDate').value;
            const grade = document.getElementById('attGrade').value;
            const section = document.getElementById('attSection').value;

            const attendanceData = {
                date: date,
                grade_level: grade,
                section: section,
                students: []
            };

            // Collect attendance data from the table
            const rows = document.querySelectorAll('#attendanceTableBody tr');
            rows.forEach(row => {
                const studentId = row.dataset.studentId;
                const statusRadios = row.querySelectorAll('input[type="radio"]');
                const remarksInput = row.querySelector('input[type="text"]');

                let status = 'present'; // default
                statusRadios.forEach(radio => {
                    if (radio.checked) {
                        status = radio.value;
                    }
                });

                attendanceData.students.push({
                    student_id: studentId,
                    status: status,
                    remarks: remarksInput.value
                });
            });

            try {
                const response = await fetch('../api/staff_attendance.php?action=save', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'payload=' + encodeURIComponent(JSON.stringify(attendanceData))
                });

                const result = await response.json();
                if (result.ok) {
                    showSuccess('Attendance saved successfully!');
                    // Refresh the summary
                    loadAttendanceSummary();
                } else {
                    showError('Failed to save attendance: ' + (result.error || 'Unknown error'));
                }
            } catch (error) {
                showError('Error saving attendance: ' + error.message);
            }
        }

        async function loadAttendanceSummary() {
            try {
                const response = await fetch('../api/staff_attendance.php?action=summary_today');
                const data = await response.json();

                if (data.ok) {
                    const summary = data.summary;
                    document.querySelector('.stat-item:nth-child(1) h3').textContent = summary.rate + '%';
                    document.querySelector('.stat-item:nth-child(2) h3').textContent = summary.total;
                    document.querySelector('.stat-item:nth-child(3) h3').textContent = new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
                    document.querySelector('.stat-item:nth-child(4) h3').textContent = new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
                }
            } catch (error) {
                console.error('Error loading attendance summary:', error);
            }
        }

        // Load attendance summary on page load
        loadAttendanceSummary();

        // Load sections from enrollment for chosen grade
        async function loadSectionsForGrade(){
            try{ const r=await fetch('../api/staff_enrollment.php?action=list'); const d=await r.json(); if(!d.ok)return; const sections=d.data.sections||{}; const g=document.getElementById('attGrade').value; const sSel=document.getElementById('attSection'); const list=sections[g]||[]; sSel.innerHTML='<option value="">Select Section</option>'+list.map(s=>`<option>${s}</option>`).join(''); }catch{}
        }
        document.getElementById('attGrade').addEventListener('change', loadSectionsForGrade);

        function generateReport(type) {
            alert('Generating ' + type + ' attendance report...');
        }
    </script>
</body>
</html>
