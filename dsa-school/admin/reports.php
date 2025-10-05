<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../api/helpers.php';
require_once __DIR__ . '/../api/data_structures.php';

$email = $_SESSION['user_email'] ?? null;
if (!$email) {
    header('Location: ../index.php');
    exit;
}

$user = get_user_by_email($email);
if (!$user || $user['role'] !== 'Administrator') {
    header('Location: ../dashboard.php');
    exit;
}

$dsManager = DataStructuresManager::getInstance();
$allUsers = read_users();
$totalActivities = $dsManager->getActivityStack()->size();
$totalNotifications = $dsManager->getNotificationQueue()->size();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>System Reports - St. Luke's School</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="../assets/js/toast.js"></script>
</head>
<body>
    <?php
        $userRole = get_role_display_name($user['role']);
        $userNotifications = array_filter($dsManager->getNotificationQueue()->getAll(), fn($n) => $n['user_email'] === $email);
        $unreadNotifications = array_filter($userNotifications, fn($n) => !$n['read']);
        $subtitle = 'System Reports'; $assetPrefix = '..'; include __DIR__ . '/../partials/header.php';
    ?>

    <main class="container">
        <section class="card">
            <h2>System Overview</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <h3><?= count($allUsers) ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3><?= $totalActivities ?></h3>
                        <p>Total Activities</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">🔔</div>
                    <div class="stat-content">
                        <h3><?= $totalNotifications ?></h3>
                        <p>Total Notifications</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3><?= count(array_filter($allUsers, fn($u) => !empty($u['totp_secret']))) ?></h3>
                        <p>2FA Enabled</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="card">
            <h2>User Distribution by Role</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Count</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $roleCount = [];
                        foreach ($allUsers as $u) {
                            $role = $u['role'];
                            $roleCount[$role] = ($roleCount[$role] ?? 0) + 1;
                        }
                        $total = count($allUsers);
                        foreach ($roleCount as $role => $count):
                            $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($role) ?></td>
                            <td><?= $count ?></td>
                            <td><?= $percentage ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <h2>Generate Reports</h2>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="action-card">
                        <h4>User Report</h4>
                        <p>Export all user data with roles and status</p>
                        <button class="btn btn-primary" onclick="exportReport('users')">📊 Export CSV</button>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="action-card">
                        <h4>Activity Report</h4>
                        <p>Export system activity logs</p>
                        <button class="btn btn-primary" onclick="exportReport('activities')">📊 Export CSV</button>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="action-card">
                        <h4>Security Report</h4>
                        <p>Export security and 2FA status</p>
                        <button class="btn btn-primary" onclick="exportReport('security')">📊 Export CSV</button>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="action-card">
                        <h4>Custom Report</h4>
                        <p>Generate custom filtered reports</p>
                        <button class="btn btn-primary" onclick="showInfo('Custom report builder coming soon!')">⚙️ Configure</button>
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

        function exportReport(type) {
            window.location.href = `../api/export.php?type=${type}&format=csv`;
        }
    </script>
</body>
</html>
