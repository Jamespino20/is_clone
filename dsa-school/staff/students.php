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

// Sample student data - in a real system, this would come from a database
$students = [
    [
        'id' => 1,
        'name' => 'Juan Dela Cruz',
        'email' => 'juan.delacruz@slssr.edu.ph',
        'student_id' => '2024-001',
        'grade_level' => 'Grade 10',
        'section' => 'St. Luke',
        'enrollment_status' => 'Enrolled',
        'tuition_balance' => 2500.00,
        'attendance_rate' => 92.5,
        'gpa' => 3.2
    ],
    [
        'id' => 2,
        'name' => 'Maria Santos',
        'email' => 'maria.santos@slssr.edu.ph',
        'student_id' => '2024-002',
        'grade_level' => 'Grade 9',
        'section' => 'St. Mark',
        'enrollment_status' => 'Enrolled',
        'tuition_balance' => 0.00,
        'attendance_rate' => 95.8,
        'gpa' => 3.8
    ],
    [
        'id' => 3,
        'name' => 'Pedro Rodriguez',
        'email' => 'pedro.rodriguez@slssr.edu.ph',
        'student_id' => '2024-003',
        'grade_level' => 'Grade 11',
        'section' => 'St. John',
        'enrollment_status' => 'Dropped',
        'tuition_balance' => 5000.00,
        'attendance_rate' => 45.2,
        'gpa' => 2.1
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Student Management - St. Luke's School</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <header class="topbar">
        <div class="topbar-left">
            <img src="../assets/img/school-logo.png" alt="School Logo" class="topbar-logo">
            <div class="topbar-title">
                <h1>Student Management</h1>
                <span class="topbar-subtitle">Staff Portal</span>
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
        <section class="card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Student Records</h2>
                <div class="d-flex gap-2">
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
                <div class="col-md-3">
                    <select id="gradeFilter" class="form-control">
                        <option value="">All Grades</option>
                        <option value="Grade 7">Grade 7</option>
                        <option value="Grade 8">Grade 8</option>
                        <option value="Grade 9">Grade 9</option>
                        <option value="Grade 10">Grade 10</option>
                        <option value="Grade 11">Grade 11</option>
                        <option value="Grade 12">Grade 12</option>
                    </select>
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
                        <h3><?= count($students) ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3><?= count(array_filter($students, fn($s) => $s['enrollment_status'] === 'Enrolled')) ?></h3>
                        <p>Enrolled</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <h3>₱<?= number_format(array_sum(array_column($students, 'tuition_balance')), 2) ?></h3>
                        <p>Outstanding Balance</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3><?= number_format(array_sum(array_column($students, 'attendance_rate')) / count($students), 1) ?>%</h3>
                        <p>Avg Attendance</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        const students = <?= json_encode($students) ?>;
        
        // Search functionality
        document.getElementById('studentSearch').addEventListener('input', filterStudents);
        document.getElementById('statusFilter').addEventListener('change', filterStudents);
        document.getElementById('gradeFilter').addEventListener('change', filterStudents);
        
        function filterStudents() {
            const searchTerm = document.getElementById('studentSearch').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const gradeFilter = document.getElementById('gradeFilter').value;
            
            const rows = document.querySelectorAll('#studentsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const status = row.cells[4].textContent.trim();
                const grade = row.cells[3].textContent.trim();
                
                const matchesSearch = text.includes(searchTerm);
                const matchesStatus = !statusFilter || status.includes(statusFilter);
                const matchesGrade = !gradeFilter || grade.includes(gradeFilter);
                
                row.style.display = (matchesSearch && matchesStatus && matchesGrade) ? '' : 'none';
            });
        }
        
        function showAddStudentModal() {
            alert('Add student functionality would be implemented here');
        }
        
        function viewStudent(id) {
            const student = students.find(s => s.id === id);
            alert(`Viewing student: ${student.name}`);
        }
        
        function editStudent(id) {
            const student = students.find(s => s.id === id);
            alert(`Editing student: ${student.name}`);
        }
        
        function viewTuition(id) {
            const student = students.find(s => s.id === id);
            alert(`Viewing tuition for: ${student.name}\nBalance: ₱${student.tuition_balance.toFixed(2)}`);
        }
        
        function exportData(format) {
            alert(`Exporting student data as ${format.toUpperCase()}...`);
        }
    </script>
</body>
</html>
