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

$payments = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Tuition Management - St. Luke's School</title>
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
        $subtitle = 'Tuition Management'; $assetPrefix = '..'; include __DIR__ . '/../partials/header.php';
    ?>

    <main class="container">
        <section class="card">
            <h2>Tuition Summary</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <h3 id="statTotalExpected">₱0</h3>
                        <p>Total Expected</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3 id="statCollected">₱0</h3>
                        <p>Collected</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-content">
                        <h3 id="statOutstanding">₱0</h3>
                        <p>Outstanding</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3 id="statCollectionRate">0%</h3>
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
                    <tbody id="tuitionTableBody">
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

        let tuitionData = [];
        
        async function loadTuition() {
            try {
                const res = await fetch('../api/tuition_api.php?action=list');
                const data = await res.json();
                if (data.ok && data.items) {
                    tuitionData = data.items;
                    renderTuition();
                    updateStats();
                }
            } catch (error) {
                console.error('Error loading tuition:', error);
            }
        }
        
        function renderTuition() {
            const tbody = document.getElementById('tuitionTableBody');
            tbody.innerHTML = tuitionData.map(record => {
                const statusClass = record.status === 'Paid' ? 'success' : (record.status === 'Partial' ? 'warning' : 'danger');
                const lastPayment = record.last_payment_date ? new Date(record.last_payment_date * 1000).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';
                return `
                    <tr>
                        <td>${escapeHtml(record.student_name || '')}</td>
                        <td>${escapeHtml(record.student_id || '')}</td>
                        <td>₱${Number(record.total_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                        <td>₱${Number(record.balance || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                        <td><span class="badge bg-${statusClass}">${escapeHtml(record.status || '')}</span></td>
                        <td>${lastPayment}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="viewDetails('${escapeHtml(record.student_email || '')}')">View</button>
                            <button class="btn btn-sm btn-success" onclick="addPayment('${escapeHtml(record.student_email || '')}', '${escapeHtml(record.student_name || '')}')">+ Payment</button>
                        </td>
                    </tr>
                `;
            }).join('');
        }
        
        function escapeHtml(text) {
            const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }
        
        function updateStats() {
            const totalExpected = tuitionData.reduce((sum, r) => sum + Number(r.total_amount || 0), 0);
            const totalCollected = tuitionData.reduce((sum, r) => sum + Number(r.paid_amount || 0), 0);
            const totalOutstanding = tuitionData.reduce((sum, r) => sum + Number(r.balance || 0), 0);
            const collectionRate = totalExpected > 0 ? Math.round((totalCollected / totalExpected) * 100) : 0;
            
            document.getElementById('statTotalExpected').textContent = '₱' + totalExpected.toLocaleString('en-US');
            document.getElementById('statCollected').textContent = '₱' + totalCollected.toLocaleString('en-US');
            document.getElementById('statOutstanding').textContent = '₱' + totalOutstanding.toLocaleString('en-US');
            document.getElementById('statCollectionRate').textContent = collectionRate + '%';
        }
        
        async function recordPayment() {
            const studentEmail = prompt('Enter student email:');
            if (!studentEmail) return;
            
            const amount = prompt('Enter payment amount:');
            if (!amount || isNaN(amount) || Number(amount) <= 0) {
                showError('Invalid amount');
                return;
            }
            
            const method = prompt('Payment method (Cash/Credit Card/Bank Transfer):', 'Cash') || 'Cash';
            const notes = prompt('Notes (optional):') || '';
            
            try {
                const formData = new URLSearchParams({
                    student_email: studentEmail,
                    amount: amount,
                    method: method,
                    notes: notes
                });
                
                const res = await fetch('../api/tuition_api.php?action=record_payment', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: formData
                });
                
                const data = await res.json();
                if (data.ok) {
                    showSuccess('Payment recorded successfully!');
                    loadTuition();
                } else {
                    showError('Error: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                showError('Error recording payment: ' + error.message);
            }
        }

        function viewDetails(studentEmail) {
            const record = tuitionData.find(r => r.student_email === studentEmail);
            if (!record) {
                showError('Record not found');
                return;
            }
            
            let details = `Student: ${record.student_name}\n`;
            details += `Student ID: ${record.student_id}\n`;
            details += `Total Amount: ₱${Number(record.total_amount || 0).toFixed(2)}\n`;
            details += `Paid Amount: ₱${Number(record.paid_amount || 0).toFixed(2)}\n`;
            details += `Balance: ₱${Number(record.balance || 0).toFixed(2)}\n`;
            details += `Status: ${record.status}\n\n`;
            
            if (record.payment_history && record.payment_history.length > 0) {
                details += 'Payment History:\n';
                record.payment_history.forEach((payment, i) => {
                    const date = new Date(payment.date * 1000).toLocaleDateString();
                    details += `${i + 1}. ${date} - ₱${Number(payment.amount).toFixed(2)} (${payment.method})\n`;
                });
            } else {
                details += 'No payment history';
            }
            
            showInfo(details);
        }

        async function addPayment(studentEmail, studentName) {
            const amount = prompt(`Enter payment amount for ${studentName}:`);
            if (!amount || isNaN(amount) || Number(amount) <= 0) {
                showError('Invalid amount');
                return;
            }
            
            const method = prompt('Payment method (Cash/Credit Card/Bank Transfer):', 'Cash') || 'Cash';
            const notes = prompt('Notes (optional):') || '';
            
            try {
                const formData = new URLSearchParams({
                    student_email: studentEmail,
                    amount: amount,
                    method: method,
                    notes: notes
                });
                
                const res = await fetch('../api/tuition_api.php?action=record_payment', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: formData
                });
                
                const data = await res.json();
                if (data.ok) {
                    showSuccess('Payment recorded successfully!');
                    loadTuition();
                } else {
                    showError('Error: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                showError('Error recording payment: ' + error.message);
            }
        }
        
        loadTuition();
    </script>
</body>
</html>
