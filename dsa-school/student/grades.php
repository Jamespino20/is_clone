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

// Sample grade data - in a real system, this would come from a database
$grades = [
    'Grade 10' => [
        'school_year' => '2023-2024',
        'semester' => 'First Semester',
        'subjects' => [
            ['code' => 'MATH', 'name' => 'Mathematics', 'units' => 3, 'q1' => 85, 'q2' => 88, 'q3' => 90, 'q4' => 87, 'final' => 87.5],
            ['code' => 'ENG', 'name' => 'English', 'units' => 3, 'q1' => 92, 'q2' => 89, 'q3' => 91, 'q4' => 88, 'final' => 90.0],
            ['code' => 'SCI', 'name' => 'Science', 'units' => 3, 'q1' => 78, 'q2' => 82, 'q3' => 85, 'q4' => 80, 'final' => 81.25],
            ['code' => 'FIL', 'name' => 'Filipino', 'units' => 3, 'q1' => 88, 'q2' => 85, 'q3' => 87, 'q4' => 89, 'final' => 87.25],
            ['code' => 'AP', 'name' => 'Araling Panlipunan', 'units' => 3, 'q1' => 90, 'q2' => 87, 'q3' => 89, 'q4' => 91, 'final' => 89.25],
            ['code' => 'PE', 'name' => 'Physical Education', 'units' => 2, 'q1' => 95, 'q2' => 92, 'q3' => 94, 'q4' => 93, 'final' => 93.5],
            ['code' => 'HEALTH', 'name' => 'Health', 'units' => 2, 'q1' => 89, 'q2' => 91, 'q3' => 88, 'q4' => 90, 'final' => 89.5],
            ['code' => 'TLE', 'name' => 'Technology and Livelihood Education', 'units' => 2, 'q1' => 85, 'q2' => 88, 'q3' => 86, 'q4' => 87, 'final' => 86.5],
        ]
    ]
];

$gpa = 0;
$totalUnits = 0;
$totalPoints = 0;

foreach ($grades['Grade 10']['subjects'] as $subject) {
    $totalUnits += $subject['units'];
    $totalPoints += $subject['final'] * $subject['units'];
}

$gpa = $totalUnits > 0 ? $totalPoints / $totalUnits : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Grades - St. Luke's School</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form137 {
            background: white;
            border: 2px solid #000;
            margin: 20px auto;
            max-width: 800px;
            font-family: 'Times New Roman', serif;
        }
        
        .form137-header {
            background: #f0f0f0;
            padding: 15px;
            text-align: center;
            border-bottom: 2px solid #000;
        }
        
        .form137-title {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
        }
        
        .form137-subtitle {
            font-size: 14px;
            margin: 5px 0 0 0;
        }
        
        .form137-content {
            padding: 20px;
        }
        
        .student-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-item {
            display: flex;
            margin-bottom: 8px;
        }
        
        .info-label {
            font-weight: bold;
            min-width: 120px;
        }
        
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .grades-table th,
        .grades-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            font-size: 12px;
        }
        
        .grades-table th {
            background: #f0f0f0;
            font-weight: bold;
        }
        
        .grades-table .subject-name {
            text-align: left;
            font-weight: bold;
        }
        
        .summary-section {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #000;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .summary-item {
            text-align: center;
        }
        
        .summary-value {
            font-size: 24px;
            font-weight: bold;
            color: #017137;
        }
        
        .summary-label {
            font-size: 12px;
            color: #666;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        @media print {
            .print-button {
                display: none;
            }
            
            .form137 {
                margin: 0;
                border: none;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="topbar-left">
            <img src="../assets/img/school-logo.png" alt="School Logo" class="topbar-logo">
            <div class="topbar-title">
                <h1>My Grades</h1>
                <span class="topbar-subtitle">DepEd Form 137</span>
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
        <button class="btn btn-primary print-button" onclick="window.print()">🖨️ Print</button>
        
        <div class="form137">
            <div class="form137-header">
                <h1 class="form137-title">REPORT CARD</h1>
                <p class="form137-subtitle">(DepEd Form 137)</p>
                <p class="form137-subtitle">St. Luke's School of San Rafael</p>
                <p class="form137-subtitle">Sampaloc, San Rafael, Bulacan</p>
            </div>
            
            <div class="form137-content">
                <div class="student-info">
                    <div>
                        <div class="info-item">
                            <span class="info-label">Student Name:</span>
                            <span><?= htmlspecialchars($user['name']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Student ID:</span>
                            <span>2024-001</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Grade Level:</span>
                            <span>Grade 10</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Section:</span>
                            <span>St. Luke</span>
                        </div>
                    </div>
                    <div>
                        <div class="info-item">
                            <span class="info-label">School Year:</span>
                            <span><?= $grades['Grade 10']['school_year'] ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Semester:</span>
                            <span><?= $grades['Grade 10']['semester'] ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date Issued:</span>
                            <span><?= date('F j, Y') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">LRN:</span>
                            <span>123456789012</span>
                        </div>
                    </div>
                </div>
                
                <table class="grades-table">
                    <thead>
                        <tr>
                            <th rowspan="2">Subject</th>
                            <th rowspan="2">Units</th>
                            <th colspan="4">Quarterly Grades</th>
                            <th rowspan="2">Final Grade</th>
                            <th rowspan="2">Remarks</th>
                        </tr>
                        <tr>
                            <th>Q1</th>
                            <th>Q2</th>
                            <th>Q3</th>
                            <th>Q4</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grades['Grade 10']['subjects'] as $subject): ?>
                        <tr>
                            <td class="subject-name"><?= htmlspecialchars($subject['name']) ?></td>
                            <td><?= $subject['units'] ?></td>
                            <td><?= $subject['q1'] ?></td>
                            <td><?= $subject['q2'] ?></td>
                            <td><?= $subject['q3'] ?></td>
                            <td><?= $subject['q4'] ?></td>
                            <td><strong><?= number_format($subject['final'], 2) ?></strong></td>
                            <td><?= $subject['final'] >= 75 ? 'PASSED' : 'FAILED' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="summary-section">
                    <h3 style="text-align: center; margin-bottom: 20px;">ACADEMIC SUMMARY</h3>
                    <div class="summary-grid">
                        <div class="summary-item">
                            <div class="summary-value"><?= number_format($gpa, 2) ?></div>
                            <div class="summary-label">GENERAL AVERAGE</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-value"><?= $totalUnits ?></div>
                            <div class="summary-label">TOTAL UNITS</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-value"><?= count(array_filter($grades['Grade 10']['subjects'], fn($s) => $s['final'] >= 75)) ?></div>
                            <div class="summary-label">SUBJECTS PASSED</div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-value"><?= $gpa >= 90 ? 'WITH HONORS' : ($gpa >= 85 ? 'WITH HIGH HONORS' : ($gpa >= 80 ? 'WITH HIGHEST HONORS' : 'PASSED')) ?></div>
                            <div class="summary-label">ACADEMIC STANDING</div>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 30px; display: flex; justify-content: space-between;">
                    <div style="text-align: center;">
                        <div style="border-top: 1px solid #000; width: 200px; margin-bottom: 5px;"></div>
                        <strong>Registrar's Signature</strong>
                    </div>
                    <div style="text-align: center;">
                        <div style="border-top: 1px solid #000; width: 200px; margin-bottom: 5px;"></div>
                        <strong>Principal's Signature</strong>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Additional Grade History -->
        <section class="card">
            <h2>Grade History</h2>
            <div class="row">
                <div class="col-md-6">
                    <h4>Current Semester Performance</h4>
                    <div class="progress mb-2">
                        <div class="progress-bar bg-success" style="width: <?= $gpa * 10 ?>%">
                            <?= number_format($gpa, 1) ?>
                        </div>
                    </div>
                    <p class="text-muted">Overall GPA: <?= number_format($gpa, 2) ?></p>
                </div>
                <div class="col-md-6">
                    <h4>Subject Performance</h4>
                    <?php foreach ($grades['Grade 10']['subjects'] as $subject): ?>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span><?= htmlspecialchars($subject['code']) ?></span>
                        <span class="badge bg-<?= $subject['final'] >= 90 ? 'success' : ($subject['final'] >= 80 ? 'warning' : 'danger') ?>">
                            <?= number_format($subject['final'], 1) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
