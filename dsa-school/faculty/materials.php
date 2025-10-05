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
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <select class="form-control">
                        <option>All Classes</option>
                        <option>Mathematics - Grade 10</option>
                        <option>Science - Grade 10</option>
                        <option>English - Grade 10</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <select class="form-control">
                        <option>All Types</option>
                        <option>Syllabus</option>
                        <option>Lecture Notes</option>
                        <option>Presentations</option>
                        <option>Worksheets</option>
                        <option>Readings</option>
                    </select>
                </div>
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
                    <tbody>
                        <tr>
                            <td>📄 Course Syllabus</td>
                            <td><span class="badge bg-primary">Syllabus</span></td>
                            <td>Mathematics - Grade 10</td>
                            <td><?= date('M j, Y', strtotime('-30 days')) ?></td>
                            <td>45</td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="viewMaterial('syllabus')">View</button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="editMaterial('syllabus')">Edit</button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteMaterial('syllabus')">Delete</button>
                            </td>
                        </tr>
                        <tr>
                            <td>📊 Chapter 5 Presentation</td>
                            <td><span class="badge bg-success">Presentation</span></td>
                            <td>Mathematics - Grade 10</td>
                            <td><?= date('M j, Y', strtotime('-7 days')) ?></td>
                            <td>32</td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="viewMaterial('presentation')">View</button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="editMaterial('presentation')">Edit</button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteMaterial('presentation')">Delete</button>
                            </td>
                        </tr>
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
                        <h3>12</h3>
                        <p>Total Materials</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📥</div>
                    <div class="stat-content">
                        <h3>245</h3>
                        <p>Total Downloads</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3>3</h3>
                        <p>Active Classes</p>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">🆕</div>
                    <div class="stat-content">
                        <h3>2</h3>
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

        function uploadMaterial() {
            alert('Upload Material dialog would open here.');
        }

        function viewMaterial(id) {
            alert(`Viewing material: ${id}`);
        }

        function editMaterial(id) {
            alert(`Editing material: ${id}`);
        }

        function deleteMaterial(id) {
            if (confirm(`Delete this material? This cannot be undone.`)) {
                alert(`Material ${id} deleted.`);
                location.reload();
            }
        }
    </script>
</body>
</html>
