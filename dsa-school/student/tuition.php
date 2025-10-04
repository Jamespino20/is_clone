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

// Sample tuition data
$tuitionData = [
    'current_balance' => 2500.00,
    'total_fees' => 15000.00,
    'paid_amount' => 12500.00,
    'payment_history' => [
        ['date' => '2024-01-15', 'amount' => 5000.00, 'method' => 'Bank Transfer', 'status' => 'Completed'],
        ['date' => '2024-02-15', 'amount' => 5000.00, 'method' => 'Credit Card', 'status' => 'Completed'],
        ['date' => '2024-03-15', 'amount' => 2500.00, 'method' => 'Cash', 'status' => 'Completed'],
    ],
    'upcoming_due' => [
        ['date' => '2024-04-15', 'amount' => 2500.00, 'description' => 'Final Payment'],
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Tuition Balance - St. Luke's School</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <header class="topbar">
        <div class="topbar-left">
            <img src="../assets/img/school-logo.png" alt="School Logo" class="topbar-logo">
            <div class="topbar-title">
                <h1>St. Luke's School of San Rafael</h1>
                <span class="topbar-subtitle">Tuition Balance</span>
            </div>
        </div>
        <div class="topbar-right">
            <div class="user-info">
                <span class="user-name">Welcome, <?= htmlspecialchars($user['name']) ?></span>
                <span class="user-role"><?= get_role_display_name($user['role']) ?></span>
            </div>
            <nav>
                <a href="../profile.php" class="nav-link">Profile</a>
                <a href="../security.php" class="nav-link">Security</a>
                <a href="../notifications.php" class="nav-link">🔔 Notifications</a>
                <a href="../api/logout.php" class="nav-link logout">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <!-- Tuition Summary -->
        <section class="card">
            <h2>💰 Tuition Summary</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">💳</div>
                    <div class="stat-content">
                        <h3>₱<?= number_format($tuitionData['current_balance'], 2) ?></h3>
                        <p>Current Balance</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3>₱<?= number_format($tuitionData['paid_amount'], 2) ?></h3>
                        <p>Total Paid</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3><?= number_format(($tuitionData['paid_amount'] / $tuitionData['total_fees']) * 100, 1) ?>%</h3>
                        <p>Payment Progress</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📅</div>
                    <div class="stat-content">
                        <h3><?= count($tuitionData['upcoming_due']) ?></h3>
                        <p>Upcoming Due</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Payment Progress Bar -->
        <section class="card">
            <h3>Payment Progress</h3>
            <div class="progress mb-3" style="height: 25px;">
                <div class="progress-bar bg-success" role="progressbar" 
                     style="width: <?= ($tuitionData['paid_amount'] / $tuitionData['total_fees']) * 100 ?>%"
                     aria-valuenow="<?= ($tuitionData['paid_amount'] / $tuitionData['total_fees']) * 100 ?>" 
                     aria-valuemin="0" aria-valuemax="100">
                    <?= number_format(($tuitionData['paid_amount'] / $tuitionData['total_fees']) * 100, 1) ?>%
                </div>
            </div>
            <div class="row text-center">
                <div class="col-md-4">
                    <strong>Total Fees:</strong> ₱<?= number_format($tuitionData['total_fees'], 2) ?>
                </div>
                <div class="col-md-4">
                    <strong>Paid:</strong> ₱<?= number_format($tuitionData['paid_amount'], 2) ?>
                </div>
                <div class="col-md-4">
                    <strong>Remaining:</strong> ₱<?= number_format($tuitionData['current_balance'], 2) ?>
                </div>
            </div>
        </section>

        <!-- Upcoming Payments -->
        <section class="card">
            <h3>📅 Upcoming Payments</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tuitionData['upcoming_due'] as $payment): ?>
                        <tr>
                            <td><?= date('M j, Y', strtotime($payment['date'])) ?></td>
                            <td><strong>₱<?= number_format($payment['amount'], 2) ?></strong></td>
                            <td><?= htmlspecialchars($payment['description']) ?></td>
                            <td><span class="badge bg-warning">Pending</span></td>
                            <td>
                                <button class="btn btn-primary btn-sm" onclick="makePayment(<?= $payment['amount'] ?>)">
                                    💳 Pay Now
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Payment History -->
        <section class="card">
            <h3>📋 Payment History</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tuitionData['payment_history'] as $payment): ?>
                        <tr>
                            <td><?= date('M j, Y', strtotime($payment['date'])) ?></td>
                            <td><strong>₱<?= number_format($payment['amount'], 2) ?></strong></td>
                            <td><?= htmlspecialchars($payment['method']) ?></td>
                            <td><span class="badge bg-success"><?= $payment['status'] ?></span></td>
                            <td>
                                <button class="btn btn-outline-primary btn-sm" onclick="downloadReceipt('<?= $payment['date'] ?>')">
                                    📄 Download
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Payment Methods -->
        <section class="card">
            <h3>💳 Payment Methods</h3>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="payment-method-card">
                        <div class="payment-icon">🏦</div>
                        <h5>Bank Transfer</h5>
                        <p class="text-muted">Direct bank transfer to school account</p>
                        <button class="btn btn-outline-primary" onclick="showBankDetails()">View Details</button>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="payment-method-card">
                        <div class="payment-icon">💳</div>
                        <h5>Credit/Debit Card</h5>
                        <p class="text-muted">Secure online payment processing</p>
                        <button class="btn btn-outline-primary" onclick="processCardPayment()">Pay Now</button>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="payment-method-card">
                        <div class="payment-icon">💰</div>
                        <h5>Cash Payment</h5>
                        <p class="text-muted">Pay at the school office</p>
                        <button class="btn btn-outline-primary" onclick="showOfficeHours()">Office Hours</button>
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
        // Dark mode functionality
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
        
        // Load dark mode preference
        document.addEventListener('DOMContentLoaded', function() {
            const darkMode = localStorage.getItem('darkMode');
            if (darkMode === 'true') {
                document.body.classList.add('dark-mode');
                document.getElementById('darkModeIcon').textContent = '☀️';
            }
        });

        function makePayment(amount) {
            if (confirm(`Process payment of ₱${amount.toFixed(2)}?`)) {
                alert('Payment processing would be implemented here. Redirecting to payment gateway...');
            }
        }

        function downloadReceipt(date) {
            alert(`Downloading receipt for payment made on ${date}...`);
        }

        function showBankDetails() {
            alert('Bank Details:\n\nAccount Name: St. Luke\'s School of San Rafael\nAccount Number: 1234567890\nBank: Sample Bank\nBranch: Main Branch');
        }

        function processCardPayment() {
            alert('Redirecting to secure payment gateway...');
        }

        function showOfficeHours() {
            alert('Office Hours:\n\nMonday - Friday: 8:00 AM - 5:00 PM\nSaturday: 8:00 AM - 12:00 PM\nSunday: Closed');
        }
    </script>

    <style>
        .payment-method-card {
            padding: 1.5rem;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            text-align: center;
            height: 100%;
            transition: all 0.3s ease;
        }
        
        .payment-method-card:hover {
            border-color: #017137;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(1, 113, 55, 0.1);
        }
        
        .payment-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        /* Dark mode styles */
        .dark-mode .payment-method-card {
            background: #2d2d2d !important;
            border-color: #555 !important;
            color: #e0e0e0 !important;
        }
        
        .dark-mode .payment-method-card:hover {
            border-color: #f7e24b !important;
            box-shadow: 0 4px 12px rgba(247, 226, 75, 0.2) !important;
        }
    </style>
</body>
</html>
