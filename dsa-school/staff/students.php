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

$students = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Student Management - St. Luke's School</title>
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
        $subtitle = 'Student Management'; $assetPrefix = '..'; include __DIR__ . '/../partials/header.php';
    ?>

    <main class="container">
        <section class="card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Student Records</h2>
                <div class="d-flex gap-2 align-items-center">
                    <select id="yearLevelSelect" class="form-control d-inline-block w-auto"></select>
                    <select id="sectionSelect" class="form-control d-inline-block w-auto"></select>
                    <button class="btn btn-outline-success" onclick="showManageSections()">Manage Sections</button>
                    <button class="btn btn-success" onclick="showAddStudentModal()">+ Add Student</button>
                    <button class="btn btn-outline-primary" onclick="exportData('csv')">📊 Export CSV</button>
                    <button class="btn btn-outline-secondary" onclick="exportData('pdf')">📄 Export PDF</button>
                </div>
            </div>
            
            <!-- Search and Filters -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <input type="text" id="studentSearch" class="form-control" placeholder="Search students by name, ID, or email...">
                </div>
                <div class="col-md-3">
                    <select id="statusFilter" class="form-control">
                        <option value="">All Status</option>
                        <option value="Enrolled">Enrolled</option>
                        <option value="Dropped">Dropped</option>
                        <option value="Graduated">Graduated</option>
                    </select>
                </div>
                <div class="col-md-3"></div>
            </div>

            <!-- Extra filters -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <input type="number" step="0.1" id="gpaMin" class="form-control" placeholder="Min GPA (e.g., 2.5)">
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="hasBalance">
                        <label class="form-check-label" for="hasBalance">Has Outstanding Balance</label>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-outline-secondary" onclick="resetFilters()">Reset Filters</button>
                    <button class="btn btn-outline-primary" onclick="exportVisibleCSV()">Export Visible CSV</button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped" id="studentsTable">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Grade/Section</th>
                            <th>Status</th>
                            <th>Tuition Balance</th>
                            <th>Attendance</th>
                            <th>GPA</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['student_id']) ?></td>
                            <td><?= htmlspecialchars($student['name']) ?></td>
                            <td><?= htmlspecialchars($student['email']) ?></td>
                            <td><?= htmlspecialchars($student['grade_level']) ?> - <?= htmlspecialchars($student['section']) ?></td>
                            <td>
                                <span class="badge bg-<?= $student['enrollment_status'] === 'Enrolled' ? 'success' : ($student['enrollment_status'] === 'Dropped' ? 'danger' : 'info') ?>">
                                    <?= htmlspecialchars($student['enrollment_status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($student['tuition_balance'] > 0): ?>
                                    <span class="text-danger">₱<?= number_format($student['tuition_balance'], 2) ?></span>
                                <?php else: ?>
                                    <span class="text-success">₱0.00</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $student['attendance_rate'] >= 90 ? 'success' : ($student['attendance_rate'] >= 75 ? 'warning' : 'danger') ?>">
                                    <?= number_format($student['attendance_rate'], 1) ?>%
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $student['gpa'] >= 3.5 ? 'success' : ($student['gpa'] >= 2.5 ? 'warning' : 'danger') ?>">
                                    <?= number_format($student['gpa'], 1) ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="viewStudent(<?= $student['id'] ?>)">View</button>
                                <button class="btn btn-sm btn-outline-warning" onclick="editStudent(<?= $student['id'] ?>)">Edit</button>
                                <button class="btn btn-sm btn-outline-info" onclick="assignToSection(<?= $student['id'] ?>, '<?= htmlspecialchars($student['student_id']) ?>', '<?= htmlspecialchars($student['grade_level']) ?>')">Assign Section</button>
                                <button class="btn btn-sm btn-outline-info" onclick="viewTuition(<?= $student['id'] ?>)">Tuition</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Statistics Cards -->
        <section class="card">
            <h2>Student Statistics</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">👨‍🎓</div>
                    <div class="stat-content">
                        <h3 id="statTotal">0</h3>
                        <p>Total Students</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3 id="statEnrolled">0</h3>
                        <p>Enrolled</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <h3 id="statOutstanding">₱0.00</h3>
                        <p>Outstanding Balance</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3 id="statAvgAttendance">0.0%</h3>
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
        // Dark mode persistence for this page
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
                const icon = document.getElementById('darkModeIcon');
                if (icon) icon.textContent = '☀️';
            }
        });
        let students = [];
        async function loadStudents(){
            try { const r = await fetch('../api/students_api.php?action=list'); const d = await r.json(); if (d.ok) { students = d.items||[]; renderStudents(); } } catch {}
        }
        function renderStudents(){
            const tbody = document.querySelector('#studentsTable tbody');
            tbody.innerHTML = (students||[]).map(s => `
                <tr>
                  <td>${escapeHtml(s.student_id||'')}</td>
                  <td>${escapeHtml(s.name||'')}</td>
                  <td>${escapeHtml(s.email||'')}</td>
                  <td>${escapeHtml(s.grade_level||'')} - ${escapeHtml(s.section||'')}</td>
                  <td><span class="badge bg-${s.enrollment_status==='Enrolled'?'success':(s.enrollment_status==='Dropped'?'danger':'info')}">${escapeHtml(s.enrollment_status||'')}</span></td>
                  <td>${Number(s.tuition_balance||0)>0?`<span class='text-danger'>₱${Number(s.tuition_balance).toFixed(2)}</span>`:`<span class='text-success'>₱0.00</span>`}</td>
                  <td><span class="badge bg-${Number(s.attendance_rate)>=90?'success':(Number(s.attendance_rate)>=75?'warning':'danger')}">${Number(s.attendance_rate||0).toFixed(1)}%</span></td>
                  <td><span class="badge bg-${Number(s.gpa)>=3.5?'success':(Number(s.gpa)>=2.5?'warning':'danger')}">${Number(s.gpa||0).toFixed(1)}</span></td>
                  <td>
                    <button class="btn btn-sm btn-outline-warning" onclick="editStudent(${s.id})">Edit</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteStudent(${s.id})">Delete</button>
                    <button class="btn btn-sm btn-outline-info" onclick="assignToSection(${s.id}, '${escapeHtml(s.student_id||'')}', '${escapeHtml(s.grade_level||'')}')">Assign Section</button>
                  </td>
                </tr>
            `).join('');
            filterStudents();
            updateStats();
        }
        function escapeHtml(x){ return (x||'').toString().replace(/[&<>"']/g, c=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[c])); }
        loadStudents();
        
        // Search functionality
        document.getElementById('studentSearch').addEventListener('input', filterStudents);
        document.getElementById('statusFilter').addEventListener('change', filterStudents);
        document.getElementById('gradeFilter').addEventListener('change', filterStudents);
        document.getElementById('yearLevelSelect').addEventListener('change', filterStudents);
        document.getElementById('sectionSelect').addEventListener('change', filterStudents);
        
        function filterStudents() {
            const searchTerm = document.getElementById('studentSearch').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const gradeFilter = document.getElementById('gradeFilter').value;
            
            const rows = document.querySelectorAll('#studentsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const status = row.cells[4].textContent.trim();
                const grade = row.cells[3].textContent.trim();
                
                const y = document.getElementById('yearLevelSelect').value;
                const s = document.getElementById('sectionSelect').value;
                const matchesSearch = text.includes(searchTerm);
                const matchesStatus = !statusFilter || status.includes(statusFilter);
                const matchesGrade = !gradeFilter || grade.includes(gradeFilter);
                const matchesY = !y || grade.includes(y);
                const matchesS = !s || grade.includes(s);
                
                row.style.display = (matchesSearch && matchesStatus && matchesGrade && matchesY && matchesS) ? '' : 'none';
            });
        }

        function updateStats(){
            const rows = Array.from(document.querySelectorAll('#studentsTable tbody tr'));
            const visible = rows.filter(r => r.style.display !== 'none');
            document.getElementById('statTotal').textContent = rows.length.toString();
            const enrolled = rows.filter(r => r.cells[4].textContent.includes('Enrolled')).length;
            document.getElementById('statEnrolled').textContent = enrolled.toString();
            let sumBal = 0, sumAtt = 0;
            visible.forEach(r => {
                const balTxt = r.cells[5].textContent.replace(/[^0-9.]/g,'');
                sumBal += parseFloat(balTxt||'0');
                const attTxt = r.cells[6].textContent.replace(/[^0-9.]/g,'');
                sumAtt += parseFloat(attTxt||'0');
            });
            document.getElementById('statOutstanding').textContent = '₱' + sumBal.toFixed(2);
            const avgAtt = visible.length ? (sumAtt/visible.length).toFixed(1) : '0.0';
            document.getElementById('statAvgAttendance').textContent = avgAtt + '%';
        }

        // Enrollment/Sections load + manage
        async function loadEnrollment() {
            try {
                const res = await fetch('../api/staff_enrollment.php?action=list');
                const data = await res.json();
                if (!data.ok) return;
                const years = data.data.yearLevels || [];
                const sections = data.data.sections || {};
                const ySel = document.getElementById('yearLevelSelect');
                const sSel = document.getElementById('sectionSelect');
                ySel.innerHTML = '<option value="">All Grades</option>' + years.map(y=>`<option>${y}</option>`).join('');
                sSel.innerHTML = '<option value="">All Sections</option>';
                ySel.onchange = () => {
                    const y = ySel.value; const list = (sections[y]||[]);
                    sSel.innerHTML = '<option value="">All Sections</option>' + list.map(s=>`<option>${s}</option>`).join('');
                    filterStudents();
                };
                sSel.onchange = filterStudents;
            } catch {}
        }
        loadEnrollment();

        async function showManageSections(){
            const level = prompt('Enter Year Level (e.g., Grade 7)');
            if (!level) return;
            await fetch('../api/staff_enrollment.php?action=add_year_level', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({ level }) });
            const section = prompt('Optionally add a Section for this level (e.g., St. Luke). Leave blank to skip.');
            if (section) {
                await fetch('../api/staff_enrollment.php?action=add_section', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({ level, section }) });
            }
            loadEnrollment();
            showSuccess('Sections updated.');
        }
        
        async function showAddStudentModal() {
            const name = prompt('Student name:'); if (!name) return;
            const student_id = prompt('Student ID:'); if (!student_id) return;
            const email = prompt('Email:'); if (!email) return;
            const grade_level = prompt('Grade Level (Kinder, Grade 1 ... Grade 10):'); if (!grade_level) return;
            const section = prompt('Section (optional):')||'';
            const status = prompt('Status (Enrolled/Dropped/Graduated):','Enrolled')||'Enrolled';
            const body = new URLSearchParams({ name, student_id, email, grade_level, section, status });
            const r = await fetch('../api/students_api.php?action=create', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body });
            const d = await r.json();
            if (!d.ok) { showError(d.error||'Failed'); return; }
            await loadStudents();
            showSuccess('Student added.');
        }
        
        function viewStudent(id) { const s = students.find(x=>x.id===id); showInfo(`Student: ${s?.name||''}\nID: ${s?.student_id||''}\nGrade: ${s?.grade_level||''} ${s?.section?'- '+s.section:''}`); }
        
        async function editStudent(id) {
            const s = students.find(x=>x.id===id); if (!s) return;
            const name = prompt('Student name:', s.name||'')||s.name;
            const email = prompt('Email:', s.email||'')||s.email;
            const grade_level = prompt('Grade Level:', s.grade_level||'')||s.grade_level;
            const section = prompt('Section (optional):', s.section||'')||'';
            const status = prompt('Status (Enrolled/Dropped/Graduated):', s.enrollment_status||'Enrolled')||'Enrolled';
            const body = new URLSearchParams({ id, name, email, grade_level, section, enrollment_status: status });
            const r = await fetch('../api/students_api.php?action=update', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body });
            const d = await r.json(); if (!d.ok){ showError(d.error||'Failed'); return; }
            await loadStudents();
        }
        async function deleteStudent(id){ if (!confirm('Delete this student?')) return; const r = await fetch('../api/students_api.php?action=delete', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({ id }) }); const d = await r.json(); if (!d.ok){ showError(d.error||'Failed'); return; } await loadStudents(); }
        
        function exportData(format) {
            if (format === 'csv') {
                exportAllStudentsCSV();
            } else if (format === 'pdf') {
                showInfo('PDF export functionality coming soon!'));
            }
        }

        async function exportAllStudentsCSV() {
            try {
                // Get all students data
                const response = await fetch('../api/students_api.php?action=list');
                const data = await response.json();

                if (!data.ok || !data.items || data.items.length === 0) {
                    showWarning('No student data available for export');
                    return;
                }

                const students = data.items;
                const csvContent = generateStudentCSV(students);

                // Create and download the file
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', `all_students_${new Date().toISOString().split('T')[0]}.csv`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                showSuccess(`Exported ${students.length} students to CSV successfully!`);
            } catch (error) {
                showError('Error exporting CSV: ' + error.message);
            }
        }

        function generateStudentCSV(students) {
            const headers = ['Student ID', 'Name', 'Email', 'Grade Level', 'Section', 'Status', 'Tuition Balance', 'Attendance Rate', 'GPA'];
            const csvRows = [headers.join(',')];

            students.forEach(student => {
                const row = [
                    `"${(student.student_id || '').replace(/"/g, '""')}"`,
                    `"${(student.name || '').replace(/"/g, '""')}"`,
                    `"${(student.email || '').replace(/"/g, '""')}"`,
                    `"${(student.grade_level || '').replace(/"/g, '""')}"`,
                    `"${(student.section || '').replace(/"/g, '""')}"`,
                    `"${(student.enrollment_status || '').replace(/"/g, '""')}"`,
                    (student.tuition_balance || 0).toFixed(2),
                    (student.attendance_rate || 0).toFixed(1),
                    (student.gpa || 0).toFixed(1)
                ];
                csvRows.push(row.join(','));
            });

            return csvRows.join('\n');
        }

        async function assignToSection(studentId, studentNumber, currentGrade) {
            // Get available sections for the student's grade level
            try {
                const res = await fetch('../api/staff_enrollment.php?action=list');
                const data = await res.json();
                if (!data.ok) return;

                const sections = data.data.sections[currentGrade] || [];
                const currentSection = students.find(s => s.id === studentId)?.section || '';

                if (sections.length === 0) {
                    showWarning('No sections available for ' + currentGrade + '. Please add sections first.');
                    return;
                }

                const sectionOptions = sections.map(s => `<option value="${s}" ${s === currentSection ? 'selected' : ''}>${s}</option>`).join('');
                const newSection = prompt(
                    'Assign section for ' + studentNumber + ' (Grade ' + currentGrade + '):\n\n' +
                    '<select id="sectionSelect">' + sectionOptions + '</select>',
                    currentSection
                );

                if (newSection && newSection !== currentSection) {
                    const response = await fetch('../api/staff_enrollment.php?action=assign_student', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: new URLSearchParams({
                            student_id: studentNumber,
                            level: currentGrade,
                            section: newSection
                        })
                    });

                    const result = await response.json();
                    if (result.ok) {
                        showSuccess('Student assigned to section: ' + newSection);
                        await loadStudents(); // Refresh the student list
                    } else {
                        showError('Failed to assign section: ' + (result.error || 'Unknown error'));
                    }
                }
            } catch (error) {
                showError('Error assigning section: ' + error.message);
            }
        }
    </script>
</body>
</html>
