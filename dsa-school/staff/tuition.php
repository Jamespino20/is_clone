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

$payments = [
    ['id' => 1, 'student' => 'Juan Dela Cruz', 'student_id' => '2024-001', 'amount' => 5000, 'balance' => 2500, 'status' => 'Partial', 'date' => time() - 86400 * 30],
    ['id' => 2, 'student' => 'Maria Santos', 'student_id' => '2024-002', 'amount' => 5000, 'balance' => 0, 'status' => 'Paid', 'date' => time() - 86400 * 15],
    ['id' => 3, 'student' => 'Pedro Rodriguez', 'student_id' => '2024-003', 'amount' => 5000, 'balance' => 5000, 'status' => 'Unpaid', 'date' => time() - 86400 * 60]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Tuition Management - St. Luke's School</title>
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
        $subtitle = 'Tuition Management'; $assetPrefix = '..'; include __DIR__ . '/../partials/header.php';
    ?>

    <main class="container">
        <section class="card">
            <h2>Tuition Summary</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <h3>₱<?= number_format(array_sum(array_column($payments, 'amount'))) ?></h3>
                        <p>Total Expected</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3>₱<?= number_format(array_sum(array_column($payments, 'amount')) - array_sum(array_column($payments, 'balance'))) ?></h3>
                        <p>Collected</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-content">
                        <h3>₱<?= number_format(array_sum(array_column($payments, 'balance'))) ?></h3>
                        <p>Outstanding</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3><?= round(((array_sum(array_column($payments, 'amount')) - array_sum(array_column($payments, 'balance'))) / array_sum(array_column($payments, 'amount'))) * 100) ?>%</h3>
                        <p>Collection Rate</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Payment Records</h2>
                <button class="btn btn-success" onclick="recordPayment()">+ Record Payment</button>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Total Amount</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Last Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= htmlspecialchars($payment['student']) ?></td>
                            <td><?= htmlspecialchars($payment['student_id']) ?></td>
                            <td>₱<?= number_format($payment['amount'], 2) ?></td>
                            <td>₱<?= number_format($payment['balance'], 2) ?></td>
                            <td>
                                <span class="badge bg-<?= $payment['status'] === 'Paid' ? 'success' : ($payment['status'] === 'Partial' ? 'warning' : 'danger') ?>">
                                    <?= htmlspecialchars($payment['status']) ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y', $payment['date']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="viewDetails(<?= $payment['id'] ?>)">View</button>
                                <button class="btn btn-sm btn-success" onclick="addPayment(<?= $payment['id'] ?>)">+ Payment</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
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

        function recordPayment() {
            alert('Record Payment dialog would open here.');
        }

        function viewDetails(id) {
            alert(`View payment details for ID: ${id}`);
        }

        function addPayment(id) {
            const amount = prompt('Enter payment amount:');
            if (amount) {
                alert(`Payment of ₱${amount} recorded successfully!`);
                location.reload();
            }
        }
    </script>
</body>
</html>
