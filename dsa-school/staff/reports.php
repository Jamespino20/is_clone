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
    <title>Reports - St. Luke's School</title>
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
        $subtitle = 'Reports'; $assetPrefix = '..'; include __DIR__ . '/../partials/header.php';
    ?>

    <main class="container">
        <section class="card">
            <h2>Generate Reports</h2>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="action-card">
                        <h4>📊 Student Enrollment Report</h4>
                        <p>View and export student enrollment data by grade level and section</p>
                        <button class="btn btn-primary" onclick="generateReport('enrollment')">Generate Report</button>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="action-card">
                        <h4>📅 Attendance Summary Report</h4>
                        <p>Generate attendance statistics and trends</p>
                        <button class="btn btn-primary" onclick="generateReport('attendance')">Generate Report</button>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="action-card">
                        <h4>💰 Tuition Collection Report</h4>
                        <p>View payment collections and outstanding balances</p>
                        <button class="btn btn-primary" onclick="generateReport('tuition')">Generate Report</button>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="action-card">
                        <h4>📈 Academic Performance Report</h4>
                        <p>Analyze student grades and academic trends</p>
                        <button class="btn btn-primary" onclick="generateReport('academic')">Generate Report</button>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="action-card">
                        <h4>👥 Class List Report</h4>
                        <p>Export class lists with student information</p>
                        <button class="btn btn-primary" onclick="generateReport('class_list')">Generate Report</button>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="action-card">
                        <h4>📋 Custom Report</h4>
                        <p>Build a custom report with your preferred filters</p>
                        <button class="btn btn-primary" onclick="generateReport('custom')">Configure</button>
                    </div>
                </div>
            </div>
        </section>

        <section class="card">
            <h2>Recent Reports</h2>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Report Name</th>
                            <th>Type</th>
                            <th>Generated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Monthly Attendance Report - <?= date('F Y') ?></td>
                            <td><span class="badge bg-info">Attendance</span></td>
                            <td><?= date('M j, Y g:i A') ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="downloadReport('attendance_' + Date.now())">Download</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
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

        function generateReport(type) {
            alert(`Generating ${type} report...\n\nThe report will be available for download shortly.`);
        }

        function downloadReport(filename) {
            alert(`Downloading report: ${filename}.pdf`);
        }
    </script>
</body>
</html>
