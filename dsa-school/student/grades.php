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

// Fetch student profile
$profileUrl = 'http://localhost:5000/api/student_data.php?action=profile';
$profileContext = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Cookie: ' . session_name() . '=' . session_id()
    ]
]);
$profileResponse = @file_get_contents($profileUrl, false, $profileContext);
$profileData = json_decode($profileResponse ?: '{}', true);
$studentProfile = $profileData['student'] ?? [];

// Fetch grades from API
$gradesUrl = 'http://localhost:5000/api/student_data.php?action=grades';
$gradesContext = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Cookie: ' . session_name() . '=' . session_id()
    ]
]);
$gradesResponse = @file_get_contents($gradesUrl, false, $gradesContext);
$gradesData = json_decode($gradesResponse ?: '{}', true);
$allGrades = $gradesData['grades'] ?? [];

// Group grades by class and quarter
$gradesByClass = [];
foreach ($allGrades as $grade) {
    $className = $grade['class'] ?? 'Unknown';
    $quarter = $grade['quarter'] ?? 'Q1';
    
    if (!isset($gradesByClass[$className])) {
        $gradesByClass[$className] = [];
    }
    
    $gradesByClass[$className][$quarter] = $grade;
}

// Calculate overall GPA
$totalAverage = 0;
$gradeCount = 0;
foreach ($allGrades as $grade) {
    if (isset($grade['average']) && $grade['average'] > 0) {
        $totalAverage += $grade['average'];
        $gradeCount++;
    }
}
$gpa = $gradeCount > 0 ? round($totalAverage / $gradeCount, 2) : 0;
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
            max-width: 900px;
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
            min-width: 140px;
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
    <?php
        require_once __DIR__ . '/../api/data_structures.php';
        $dsManager = DataStructuresManager::getInstance();
        $userRole = get_role_display_name($user['role']);
        $userNotifications = array_filter($dsManager->getNotificationQueue()->getAll(), fn($n) => $n['user_email'] === $email);
        $unreadNotifications = array_filter($userNotifications, fn($n) => !$n['read']);
        $subtitle = 'DepEd Form 137'; $assetPrefix = '..'; include __DIR__ . '/../partials/header.php';
    ?>

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
                            <span class="info-label">Email:</span>
                            <span><?= htmlspecialchars($email) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Grade Level:</span>
                            <span><?= htmlspecialchars((string)($studentProfile['grade_level'] ?? 'Not Assigned')) ?></span>
                        </div>
                    </div>
                    <div>
                        <div class="info-item">
                            <span class="info-label">Section:</span>
                            <span><?= htmlspecialchars($studentProfile['section'] ?? 'Not Assigned') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">School Year:</span>
                            <span>2024-2025</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date Issued:</span>
                            <span><?= date('F j, Y') ?></span>
                        </div>
                    </div>
                </div>
                
                <?php if (empty($gradesByClass)): ?>
                    <div class="alert alert-info">
                        <p class="mb-0">No grades recorded yet. Your grades will appear here once faculty members enter them.</p>
                    </div>
                <?php else: ?>
                    <table class="grades-table">
                        <thead>
                            <tr>
                                <th rowspan="2" class="subject-name">Subject/Class</th>
                                <th colspan="4">Quarters</th>
                                <th rowspan="2">Average</th>
                            </tr>
                            <tr>
                                <th>Q1</th>
                                <th>Q2</th>
                                <th>Q3</th>
                                <th>Q4</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($gradesByClass as $className => $quarters): ?>
                            <tr>
                                <td class="subject-name"><?= htmlspecialchars($className) ?></td>
                                <?php
                                $quarterGrades = [];
                                $quarterCount = 0;
                                $totalGrade = 0;
                                
                                foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $q) {
                                    if (isset($quarters[$q]) && isset($quarters[$q]['average'])) {
                                        $grade = $quarters[$q]['average'];
                                        echo '<td>' . number_format($grade, 2) . '</td>';
                                        if ($grade > 0) {
                                            $totalGrade += $grade;
                                            $quarterCount++;
                                        }
                                    } else {
                                        echo '<td>-</td>';
                                    }
                                }
                                
                                $classAverage = $quarterCount > 0 ? $totalGrade / $quarterCount : 0;
                                ?>
                                <td><strong><?= $classAverage > 0 ? number_format($classAverage, 2) : '-' ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="summary-section">
                        <h3 style="margin-top: 0;">Academic Summary</h3>
                        <div class="summary-grid">
                            <div class="summary-item">
                                <div class="summary-value"><?= $gpa ?></div>
                                <div class="summary-label">Overall GPA</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-value"><?= count($gradesByClass) ?></div>
                                <div class="summary-label">Subjects Enrolled</div>
                            </div>
                            <div class="summary-item">
                                <div class="summary-value"><?= $gradeCount ?></div>
                                <div class="summary-label">Total Grades Recorded</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
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
</body>
</html>
