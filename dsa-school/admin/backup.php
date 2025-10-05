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
if (!$user || $user['role'] !== 'Administrator') {
    header('Location: ../dashboard.php');
    exit;
}

$dataDir = __DIR__ . '/../api/data';
$backupFiles = glob($dataDir . '/backup_*.json');
rsort($backupFiles);
$backups = array_map(function($file) {
    return [
        'filename' => basename($file),
        'size' => filesize($file),
        'date' => filemtime($file)
    ];
}, $backupFiles);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>System Backup - St. Luke's School</title>
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
        $userNotifications = array_filter($dsManager->getNotificationQueue()->getAll(), function($n) use ($email) {
            return $n['user_email'] === $email;
        });
        $unreadNotifications = array_filter($userNotifications, function($n) {
            return !$n['read'];
        });
        $subtitle = 'System Backup'; $assetPrefix = '..'; include __DIR__ . '/../partials/header.php';
    ?>

    <main class="container">
        <section class="card">
            <h2>Create Backup</h2>
            <p>Create a backup of all system data including users, activities, and notifications.</p>
            <button onclick="createBackup()" class="btn btn-success">💾 Create New Backup</button>
        </section>

        <section class="card">
            <h2>Existing Backups</h2>
            <?php if (empty($backups)): ?>
                <p class="text-muted">No backups found. Create your first backup above.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Filename</th>
                                <th>Size</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="backupsTableBody">
                            <?php foreach ($backups as $backup): ?>
                            <tr>
                                <td><?= htmlspecialchars($backup['filename']) ?></td>
                                <td><?= number_format($backup['size'] / 1024, 2) ?> KB</td>
                                <td><?= date('M j, Y g:i A', $backup['date']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="downloadBackup('<?= htmlspecialchars($backup['filename']) ?>')">Download</button>
                                    <button class="btn btn-sm btn-warning" onclick="restoreBackup('<?= htmlspecialchars($backup['filename']) ?>')">Restore</button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteBackup('<?= htmlspecialchars($backup['filename']) ?>')">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2>Restore from File</h2>
            <p>Upload a backup file to restore system data.</p>
            <form id="restoreForm" enctype="multipart/form-data">
                <div class="mb-3">
                    <input type="file" class="form-control" id="backupFile" accept=".json" required>
                </div>
                <button type="submit" class="btn btn-warning">⚠️ Restore from File</button>
            </form>
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

            // Load backups list
            loadBackups();
        });

        async function loadBackups() {
            try {
                const response = await fetch('../api/backup_api.php?action=list');
                const data = await response.json();

                if (data.ok && data.backups) {
                    displayBackups(data.backups);
                }
            } catch (error) {
                console.error('Error loading backups:', error);
            }
        }

        function displayBackups(backups) {
            const tbody = document.querySelector('#backupsTableBody');
            if (!tbody) return;

            if (backups.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No backups found. Create your first backup above.</td></tr>';
                return;
            }

            tbody.innerHTML = backups.map(backup => `
                <tr>
                    <td>${escapeHtml(backup.filename)}</td>
                    <td>${(backup.size / 1024).toFixed(2)} KB</td>
                    <td>${new Date(backup.date * 1000).toLocaleString()}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="downloadBackup('${escapeHtml(backup.filename)}')">Download</button>
                        <button class="btn btn-sm btn-warning" onclick="restoreBackup('${escapeHtml(backup.filename)}')">Restore</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteBackup('${escapeHtml(backup.filename)}')">Delete</button>
                    </td>
                </tr>
            `).join('');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function createBackup() {
            if (confirm('Create a new backup of all system data?')) {
                fetch('../api/backup_api.php?action=create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.ok) {
                        showSuccess(`Backup created successfully: ${data.filename}`);
                        location.reload();
                    } else {
                        showError('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    showError('Error: ' + error);
                });
            }
        }

        function downloadBackup(filename) {
            window.location.href = `../api/data/${filename}`;
        }

        function restoreBackup(filename) {
            if (confirm(`WARNING: This will restore data from ${filename} and overwrite current data. Continue?`)) {
                fetch('../api/backup_api.php?action=restore', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'filename=' + encodeURIComponent(filename)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.ok) {
                        showSuccess('Backup restored successfully!');
                        location.reload();
                    } else {
                        showError('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    showError('Error: ' + error);
                });
            }
        }

        function deleteBackup(filename) {
            if (confirm(`Delete backup ${filename}? This cannot be undone.`)) {
                fetch('../api/backup_api.php?action=delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'filename=' + encodeURIComponent(filename)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.ok) {
                        showSuccess('Backup deleted successfully!');
                        location.reload();
                    } else {
                        showError('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    showError('Error: ' + error);
                });
            }
        }

        document.getElementById('restoreForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const file = document.getElementById('backupFile').files[0];
            if (file) {
                if (confirm(`WARNING: Restore from ${file.name}? This will overwrite current data.`)) {
                    const formData = new FormData();
                    formData.append('backup_file', file);
                    formData.append('action', 'restore_upload');

                    fetch('../api/backup_api.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.ok) {
                            showSuccess('Backup restored successfully!');
                            location.reload();
                        } else {
                            showError('Error: ' + data.error);
                        }
                    })
                    .catch(error => {
                        showError('Error: ' + error);
                    });
                }
            }
        });
    </script>
</body>
</html>
