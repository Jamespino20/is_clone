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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Tuition Balance - St. Luke's School</title>
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
        $subtitle = 'Tuition Balance'; $assetPrefix = '..'; include __DIR__ . '/../partials/header.php';
    ?>

    <main class="container">
        <section class="card">
            <h2>💰 Tuition Summary</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">💳</div>
                    <div class="stat-content">
                        <h3 id="statBalance">₱0.00</h3>
                        <p>Current Balance</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3 id="statPaid">₱0.00</h3>
                        <p>Total Paid</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3 id="statProgress">0%</h3>
                        <p>Payment Progress</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📅</div>
                    <div class="stat-content">
                        <h3 id="statTotal">₱0.00</h3>
                        <p>Total Fees</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="card">
            <h3>Payment Progress</h3>
            <div class="progress mb-3" style="height: 25px;">
                <div id="progressBar" class="progress-bar bg-success" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                    0%
                </div>
            </div>
            <div class="row text-center">
                <div class="col-md-4">
                    <strong>Total Fees:</strong> <span id="displayTotal">₱0.00</span>
                </div>
                <div class="col-md-4">
                    <strong>Paid:</strong> <span id="displayPaid">₱0.00</span>
                </div>
                <div class="col-md-4">
                    <strong>Remaining:</strong> <span id="displayBalance">₱0.00</span>
                </div>
            </div>
        </section>

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
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody id="paymentHistory">
                        <tr>
                            <td colspan="5" class="text-center">Loading payment history...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

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

    <div class="dark-mode-toggle" onclick="toggleDarkMode()">
        <span id="darkModeIcon">🌙</span>
    </div>

    <script>
        let tuitionData = null;
        
        async function loadTuition() {
            try {
                const res = await fetch('../api/tuition_api.php?action=list');
                const data = await res.json();
                if (data.ok && data.item) {
                    tuitionData = data.item;
                    updateDisplay();
                } else {
                    console.error('Error loading tuition:', data.error);
                    document.getElementById('paymentHistory').innerHTML = '<tr><td colspan="5" class="text-center text-muted">No tuition data available</td></tr>';
                }
            } catch (error) {
                console.error('Error loading tuition:', error);
                document.getElementById('paymentHistory').innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading data</td></tr>';
            }
        }
        
        function updateDisplay() {
            if (!tuitionData) return;
            
            const total = Number(tuitionData.total_amount || 0);
            const paid = Number(tuitionData.paid_amount || 0);
            const balance = Number(tuitionData.balance || 0);
            const progress = total > 0 ? Math.round((paid / total) * 100) : 0;
            
            document.getElementById('statBalance').textContent = '₱' + balance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('statPaid').textContent = '₱' + paid.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('statProgress').textContent = progress + '%';
            document.getElementById('statTotal').textContent = '₱' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            
            document.getElementById('progressBar').style.width = progress + '%';
            document.getElementById('progressBar').setAttribute('aria-valuenow', progress.toString());
            document.getElementById('progressBar').textContent = progress + '%';
            
            document.getElementById('displayTotal').textContent = '₱' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('displayPaid').textContent = '₱' + paid.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('displayBalance').textContent = '₱' + balance.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            
            const history = tuitionData.payment_history || [];
            const tbody = document.getElementById('paymentHistory');
            
            if (history.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No payment history</td></tr>';
            } else {
                tbody.innerHTML = history.map(payment => {
                    const date = new Date(payment.date * 1000).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    const amount = Number(payment.amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    return `
                        <tr>
                            <td>${date}</td>
                            <td><strong>₱${amount}</strong></td>
                            <td>${escapeHtml(payment.method || 'N/A')}</td>
                            <td><span class="badge bg-success">Completed</span></td>
                            <td>${escapeHtml(payment.notes || '')}</td>
                        </tr>
                    `;
                }).join('');
            }
        }
        
        function escapeHtml(text) {
            const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }
        
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
            loadTuition();
        });

        function showBankDetails() {
            showInfo('Bank Details:\n\nAccount Name: St. Luke\'s School of San Rafael\nAccount Number: 1234567890\nBank: Sample Bank\nBranch: Main Branch');
        }

        function processCardPayment() {
            if (!tuitionData || tuitionData.balance <= 0) {
                showWarning('No outstanding balance!');
                return;
            }
            showInfo('Payment gateway integration would be implemented here.\n\nOutstanding balance: ₱' + Number(tuitionData.balance).toFixed(2));
        }

        function showOfficeHours() {
            showInfo('Office Hours:\n\nMonday - Friday: 8:00 AM - 5:00 PM\nSaturday: 8:00 AM - 12:00 PM\nSunday: Closed');
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
