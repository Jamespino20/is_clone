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

$users = read_users();

// For shared header: compute role display and unread notifications
$userRole = get_role_display_name($user['role']);
$dsManager = DataStructuresManager::getInstance();
$allNotifications = $dsManager->getNotificationQueue()->getAll();
$userNotifications = array_filter($allNotifications, function($n) use ($email, $user) {
    return $n['user_email'] === $email || (isset($n['is_system']) && $n['is_system'] && (empty($n['target_roles']) || in_array($user['role'], $n['target_roles'])));
});
$unreadNotifications = array_filter($userNotifications, fn($n) => !$n['read']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>User Management - St. Luke's School</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php $subtitle = 'User Management'; $assetPrefix = '..'; include __DIR__ . '/../partials/header.php'; ?>

    <main class="container">
        <section class="card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>All Users</h2>
                <button class="btn btn-success" onclick="showAddUserModal()">+ Add User</button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>2FA</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $index => $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['name']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <span class="badge bg-<?= $u['role'] === 'Administrator' ? 'danger' : ($u['role'] === 'Faculty' ? 'primary' : 'success') ?>">
                                    <?= htmlspecialchars($u['role']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($u['totp_secret'])): ?>
                                    <span class="badge bg-success">Enabled</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Disabled</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M j, Y', $u['created']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="editUser(<?= $index ?>)">Edit</button>
                                <?php if ($u['email'] !== $email): ?>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?= $index ?>)">Delete</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Dark Mode Toggle -->
    <div class="dark-mode-toggle" onclick="toggleDarkMode()">
        <span id="darkModeIcon">🌙</span>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="modal-backdrop" style="display: none;">
        <div class="modal-dialog">
            <div class="modal-content">
                <button class="modal-close" onclick="closeUserModal()">&times;</button>
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add User</h5>
                </div>
                <div class="modal-body">
                    <form id="userForm">
                        <input type="hidden" id="userIndex" name="index">
                        <div class="mb-2">
                            <label class="form-label">Full Name</label>
                            <input type="text" id="userName" name="name" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Email</label>
                            <input type="email" id="userEmail" name="email" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Role</label>
                            <select id="userRole" name="role" class="form-control" required>
                                <option value="Student">Student</option>
                                <option value="Faculty">Faculty</option>
                                <option value="Staff">Staff</option>
                                <option value="Administrator">Administrator</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Password</label>
                            <input type="password" id="userPassword" name="password" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Enable 2FA</label>
                            <input type="checkbox" id="user2FA" name="enable_2fa" class="form-check-input">
                        </div>
                        <button type="submit" class="btn btn-success w-100">Save User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
        const users = <?= json_encode($users) ?>;
        
        function showAddUserModal() {
            document.getElementById('modalTitle').textContent = 'Add User';
            document.getElementById('userForm').reset();
            document.getElementById('userIndex').value = '';
            document.getElementById('userModal').style.display = 'flex';
        }
        
        function editUser(index) {
            const user = users[index];
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('userIndex').value = index;
            document.getElementById('userName').value = user.name;
            document.getElementById('userEmail').value = user.email;
            document.getElementById('userRole').value = user.role;
            document.getElementById('userPassword').value = '';
            document.getElementById('userPassword').required = false;
            // reflect enabled flag (defaults true if secret exists and flag missing)
            const enabled = (user.twofa_enabled === undefined ? !!user.totp_secret : !!user.twofa_enabled);
            document.getElementById('user2FA').checked = enabled;
            document.getElementById('userModal').style.display = 'flex';
        }
        
        function closeUserModal() {
            document.getElementById('userModal').style.display = 'none';
        }
        
        async function deleteUser(index) {
            const user = users[index];
            if (!user) return;
            if (user.email === <?= json_encode($email) ?>) { alert('You cannot delete your own account.'); return; }
            if (!confirm('Are you sure you want to delete this user?')) return;
            const res = await fetch('../api/users_delete.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({ email: user.email }) });
            const data = await res.json();
            if (!data.ok) { alert(data.error || 'Delete failed'); return; }
            location.reload();
        }
        
        document.getElementById('userForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const index = document.getElementById('userIndex').value;
            const payload = new URLSearchParams({
              name: document.getElementById('userName').value.trim(),
              email: document.getElementById('userEmail').value.trim(),
              role: document.getElementById('userRole').value,
              password: document.getElementById('userPassword').value,
              enable_2fa: document.getElementById('user2FA').checked ? '1' : '0'
            });
            try {
              const isEdit = index !== '';
              const endpoint = isEdit ? '../api/users_update.php' : '../api/users_create.php';
              const res = await fetch(endpoint, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: payload });
              const data = await res.json();
              if (!data.ok) { alert(data.error || 'Save failed'); return; }
              closeUserModal();
              location.reload();
            } catch (err) {
              alert('Network error');
            }
        });
    </script>
</body>
</html>
