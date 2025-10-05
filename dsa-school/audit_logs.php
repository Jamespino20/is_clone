<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/api/helpers.php';
require_once __DIR__ . '/api/data_structures.php';

$email = $_SESSION['user_email'] ?? null;
if (!$email) {
    header('Location: index.php');
    exit;
}

$user = get_user_by_email($email);
if (!$user) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$userRole = get_role_display_name($user['role']);

// Only administrators can access audit logs
if ($userRole !== 'Administrator') {
    header('Location: dashboard.php');
    exit;
}

$dsManager = DataStructuresManager::getInstance();
$userRole = get_role_display_name($user['role']);
$userNotifications = array_filter($dsManager->getNotificationQueue()->getAll(), fn($n) => $n['user_email'] === $email);
$unreadNotifications = array_filter($userNotifications, fn($n) => !$n['read']);

// Handle export requests
if ($_GET['export'] ?? false) {
    $format = $_GET['format'] ?? 'csv';
    $type = $_GET['type'] ?? 'all';
    
    $filename = "audit_logs_" . date('Y-m-d_H-i-s');
    
    switch ($format) {
        case 'csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            
            $logs = $dsManager->getSystemLogStack()->getAllLogs();
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Timestamp', 'User Email', 'Action', 'Details', 'IP Address']);
            
            foreach ($logs as $log) {
                fputcsv($output, [
                    date('Y-m-d H:i:s', $log['timestamp']),
                    $log['user_email'],
                    $log['action'],
                    $log['details'],
                    $log['ip_address']
                ]);
            }
            fclose($output);
            exit;
            
        case 'json':
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $filename . '.json"');
            
            $logs = $dsManager->getSystemLogStack()->getAllLogs();
            echo json_encode($logs, JSON_PRETTY_PRINT);
            exit;
    }
}

// Get all logs
$allLogs = $dsManager->getSystemLogStack()->getAllLogs();
$filteredLogs = $allLogs;

// Apply filters
$filterUser = $_GET['user'] ?? '';
$filterAction = $_GET['action'] ?? '';
$filterDate = $_GET['date'] ?? '';

if ($filterUser) {
    $filteredLogs = array_filter($filteredLogs, fn($log) => strpos($log['user_email'], $filterUser) !== false);
}

if ($filterAction) {
    $filteredLogs = array_filter($filteredLogs, fn($log) => strpos($log['action'], $filterAction) !== false);
}

if ($filterDate) {
    $targetTimestamp = strtotime($filterDate);
    $filteredLogs = array_filter($filteredLogs, fn($log) => date('Y-m-d', $log['timestamp']) === date('Y-m-d', $targetTimestamp));
}

// Pagination
$page = (int)($_GET['page'] ?? 1);
$perPage = 50;
$totalLogs = count($filteredLogs);
$totalPages = ceil($totalLogs / $perPage);
$offset = ($page - 1) * $perPage;
$paginatedLogs = array_slice($filteredLogs, $offset, $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Audit Logs - St. Luke's School</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php $subtitle = 'Audit Logs'; $assetPrefix = ''; include __DIR__ . '/partials/header.php'; ?>

    <main class="container">
        <!-- Filters and Export -->
        <section class="card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>System Audit Logs</h2>
                <div class="d-flex gap-2">
                    <a href="?export=1&format=csv" class="btn btn-outline-success">📊 Export CSV</a>
                    <a href="?export=1&format=json" class="btn btn-outline-info">📄 Export JSON</a>
                </div>
            </div>
            
            <!-- Filters -->
            <form method="GET" class="row g-3 mb-3">
                <div class="col-md-3">
                    <label for="userFilter" class="form-label">Filter by User</label>
                    <input type="text" id="userFilter" name="user" class="form-control" 
                           value="<?= htmlspecialchars($filterUser) ?>" placeholder="Email or name">
                </div>
                <div class="col-md-3">
                    <label for="actionFilter" class="form-label">Filter by Action</label>
                    <input type="text" id="actionFilter" name="action" class="form-control" 
                           value="<?= htmlspecialchars($filterAction) ?>" placeholder="Action type">
                </div>
                <div class="col-md-3">
                    <label for="dateFilter" class="form-label">Filter by Date</label>
                    <input type="date" id="dateFilter" name="date" class="form-control" 
                           value="<?= htmlspecialchars($filterDate) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">🔍 Filter</button>
                        <a href="audit_logs.php" class="btn btn-outline-secondary">Clear</a>
                    </div>
                </div>
            </form>
            
            <!-- Statistics -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-icon">📊</div>
                        <div class="stat-content">
                            <h3><?= $totalLogs ?></h3>
                            <p>Total Logs</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-icon">👥</div>
                        <div class="stat-content">
                            <h3><?= count(array_unique(array_column($allLogs, 'user_email'))) ?></h3>
                            <p>Active Users</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-icon">📅</div>
                        <div class="stat-content">
                            <h3><?= count(array_filter($allLogs, fn($log) => date('Y-m-d', $log['timestamp']) === date('Y-m-d'))) ?></h3>
                            <p>Today's Activity</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-icon">🔍</div>
                        <div class="stat-content">
                            <h3><?= count($filteredLogs) ?></h3>
                            <p>Filtered Results</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Logs Table -->
        <section class="card">
            <h3>Activity Logs</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($paginatedLogs)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No logs found matching your criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($paginatedLogs as $log): ?>
                            <tr>
                                <td>
                                    <strong><?= date('M j, Y', $log['timestamp']) ?></strong><br>
                                    <small class="text-muted"><?= date('g:i:s A', $log['timestamp']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?= htmlspecialchars($log['user_email']) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= htmlspecialchars($log['action']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($log['details']) ?></td>
                                <td>
                                    <code><?= htmlspecialchars($log['ip_address']) ?></code>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Log pagination">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&user=<?= urlencode($filterUser) ?>&action=<?= urlencode($filterAction) ?>&date=<?= urlencode($filterDate) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </section>

        <!-- Data Structure Statistics -->
        <section class="card">
            <h3>Data Structure Usage</h3>
            <div class="row">
                <div class="col-md-6">
                    <h4>Stacks</h4>
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between">
                            Activity Stack
                            <span class="badge bg-primary"><?= $dsManager->getActivityStack()->size() ?> items</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            Grade History Stack
                            <span class="badge bg-success"><?= $dsManager->getGradeStack()->size() ?> items</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            System Log Stack
                            <span class="badge bg-info"><?= $dsManager->getSystemLogStack()->size() ?> items</span>
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h4>Queues</h4>
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between">
                            Notification Queue
                            <span class="badge bg-warning"><?= $dsManager->getNotificationQueue()->size() ?> items</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            Assignment Queue
                            <span class="badge bg-secondary"><?= $dsManager->getAssignmentQueue()->size() ?> items</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            Payment Queue
                            <span class="badge bg-danger"><?= $dsManager->getPaymentQueue()->size() ?> items</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>
    </main>

    <!-- Dark Mode Toggle -->
    <div class="dark-mode-toggle" onclick="toggleDarkMode()">
        <span id="darkModeIcon">🌙</span>
    </div>

    <script>
        // Dark mode persistence
        document.addEventListener('DOMContentLoaded', function() {
            const darkMode = localStorage.getItem('darkMode');
            if (darkMode === 'true') {
                document.body.classList.add('dark-mode');
                document.getElementById('darkModeIcon').textContent = '☀️';
            }
        });
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
    </script>
</body>
</html>
