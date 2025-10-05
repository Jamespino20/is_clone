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
if (!$user || !has_permission(get_role_display_name($user['role']), 'Faculty')) {
    header('Location: ../dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Class Materials - St. Luke's School</title>
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
        $subtitle = 'Class Materials'; $assetPrefix = '..'; include __DIR__ . '/../partials/header.php';
    ?>

    <main class="container">
        <section class="card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Course Materials</h2>
                <button class="btn btn-success" onclick="uploadMaterial()">+ Upload Material</button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Material Name</th>
                            <th>Type</th>
                            <th>Class</th>
                            <th>Uploaded</th>
                            <th>Downloads</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="materialsTableBody">
                        <tr><td colspan="6" class="text-center">Loading materials...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <h2>Material Statistics</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">📁</div>
                    <div class="stat-content">
                        <h3 id="statTotal">0</h3>
                        <p>Total Materials</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📥</div>
                    <div class="stat-content">
                        <h3 id="statDownloads">0</h3>
                        <p>Total Downloads</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3 id="statClasses">0</h3>
                        <p>Classes Covered</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">🆕</div>
                    <div class="stat-content">
                        <h3 id="statRecent">0</h3>
                        <p>This Week</p>
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
        let materials = [];

        async function loadMaterials() {
            try {
                const res = await fetch('../api/materials_api.php?action=list');
                const data = await res.json();
                if (data.ok && data.items) {
                    materials = data.items;
                    renderMaterials();
                    updateStats();
                } else {
                    document.getElementById('materialsTableBody').innerHTML = '<tr><td colspan="6" class="text-center text-muted">No materials yet</td></tr>';
                }
            } catch (error) {
                console.error('Error loading materials:', error);
                document.getElementById('materialsTableBody').innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading materials</td></tr>';
            }
        }

        function renderMaterials() {
            const tbody = document.getElementById('materialsTableBody');
            if (materials.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No materials yet. Upload one to get started!</td></tr>';
                return;
            }

            tbody.innerHTML = materials.map(material => {
                const uploadedDate = new Date(material.uploaded * 1000).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                const typeBadge = material.type === 'Syllabus' ? 'primary' : (material.type === 'Presentation' ? 'success' : 'info');
                return `
                    <tr>
                        <td>📄 ${escapeHtml(material.name || '')}</td>
                        <td><span class="badge bg-${typeBadge}">${escapeHtml(material.type || '')}</span></td>
                        <td>${escapeHtml(material.class || '')}</td>
                        <td>${uploadedDate}</td>
                        <td>${material.downloads || 0}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="viewMaterial(${material.id})">View</button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteMaterial(${material.id})">Delete</button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function updateStats() {
            const total = materials.length;
            const totalDownloads = materials.reduce((sum, m) => sum + (m.downloads || 0), 0);
            const uniqueClasses = new Set(materials.map(m => m.class)).size;
            const oneWeekAgo = Date.now() / 1000 - (7 * 24 * 60 * 60);
            const recent = materials.filter(m => m.uploaded >= oneWeekAgo).length;

            document.getElementById('statTotal').textContent = total;
            document.getElementById('statDownloads').textContent = totalDownloads;
            document.getElementById('statClasses').textContent = uniqueClasses;
            document.getElementById('statRecent').textContent = recent;
        }

        function escapeHtml(text) {
            const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        async function uploadMaterial() {
            const name = prompt('Material name:');
            if (!name) return;

            const type = prompt('Type (Syllabus/Lecture Notes/Presentation/Worksheets/Readings):', 'Lecture Notes') || 'Lecture Notes';
            const className = prompt('Class (e.g., Mathematics - Grade 10):');
            if (!className) return;

            const url = prompt('Material URL (optional, for downloads):') || '#';
            const size = prompt('File size (optional):', '1.0 MB') || '1.0 MB';

            try {
                const formData = new URLSearchParams({
                    name, type, class: className, url, size
                });

                const res = await fetch('../api/materials_api.php?action=upload', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: formData
                });

                const data = await res.json();
                if (data.ok) {
                    showSuccess('Material uploaded successfully!');
                    loadMaterials();
                } else {
                    showError('Error: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                showError('Error uploading material: ' + error.message);
            }
        }

        function viewMaterial(id) {
            const material = materials.find(m => m.id === id);
            if (!material) return;

            if (material.url && material.url !== '#') {
                window.open(material.url, '_blank');
            } else {
                showWarning('No download URL available for this material.');
            }
        }

        async function deleteMaterial(id) {
            if (!confirm('Delete this material? This cannot be undone.')) return;

            try {
                const res = await fetch('../api/materials_api.php?action=delete', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({ id })
                });

                const data = await res.json();
                if (data.ok) {
                    showSuccess('Material deleted!');
                    loadMaterials();
                } else {
                    showError('Error: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                showError('Error deleting material: ' + error.message);
            }
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
            loadMaterials();
        });
    </script>
</body>
</html>
