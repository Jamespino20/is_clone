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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>System Settings - St. Luke's School</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
        $subtitle = 'System Settings'; $assetPrefix = '..'; include __DIR__ . '/../partials/header.php';
    ?>

    <main class="container">
        <section class="card">
            <h2>General Settings</h2>
            <form id="generalSettings">
                <div class="mb-3">
                    <label class="form-label">School Name</label>
                    <input type="text" class="form-control" name="school_name" value="St. Luke's School of San Rafael" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">School Email</label>
                    <input type="email" class="form-control" name="school_email" value="info@slssr.edu.ph" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">School Address</label>
                    <input type="text" class="form-control" name="school_address" value="Sampaloc, San Rafael, Bulacan" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Contact Number</label>
                    <input type="tel" class="form-control" name="school_phone" value="+63 912 345 6789" required>
                </div>
                <button type="submit" class="btn btn-success">Save Changes</button>
            </form>
        </section>

        <section class="card">
            <h2>Security Settings</h2>
            <form id="securitySettings">
                <div class="mb-3">
                    <label class="form-label">Require 2FA for All Users</label>
                    <select class="form-control" name="require_2fa">
                        <option value="optional">Optional</option>
                        <option value="required">Required</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Session Timeout (minutes)</label>
                    <input type="number" class="form-control" name="session_timeout" value="30" min="5" max="120" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password Minimum Length</label>
                    <input type="number" class="form-control" name="password_min_length" value="8" min="6" max="32" required>
                </div>
                <button type="submit" class="btn btn-success">Save Security Settings</button>
            </form>
        </section>

        <section class="card">
            <h2>Academic Year Settings</h2>
            <form id="academicSettings">
                <div class="mb-3">
                    <label class="form-label">Current Academic Year</label>
                    <input type="text" class="form-control" name="academic_year" value="2024-2025" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Current Semester</label>
                    <select class="form-control" name="current_semester">
                        <option value="1">First Semester</option>
                        <option value="2">Second Semester</option>
                        <option value="summer">Summer</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Enrollment Period Start</label>
                    <input type="date" class="form-control" name="enrollment_start" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Enrollment Period End</label>
                    <input type="date" class="form-control" name="enrollment_end" required>
                </div>
        <section class="card">
            <h2>Academic Configuration</h2>
            <form id="academicConfigSettings">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Grade Levels</label>
                        <div class="mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="grade_levels[]" value="Kinder" id="gradeKinder" checked>
                                <label class="form-check-label" for="gradeKinder">Kinder</label>
                            </div>
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="grade_levels[]" value="Grade <?= $i ?>" id="grade<?= $i ?>" checked>
                                <label class="form-check-label" for="grade<?= $i ?>">Grade <?= $i ?></label>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Semester System</label>
                        <select class="form-control mb-3" name="semester_system">
                            <option value="quarterly">Quarterly (4 quarters)</option>
                            <option value="semester">Semester (2 semesters)</option>
                            <option value="summer">Summer Classes</option>
                        </select>

                        <label class="form-label">Grading Scale</label>
                        <div class="mb-3">
                            <div class="row">
                                <div class="col-3">
                                    <label class="form-label">Grade</label>
                                </div>
                                <div class="col-3">
                                    <label class="form-label">Min %</label>
                                </div>
                                <div class="col-3">
                                    <label class="form-label">Max %</label>
                                </div>
                                <div class="col-3">
                                    <label class="form-label">GPA</label>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-3">
                                    <input type="text" class="form-control form-control-sm" name="grading_scale[A][grade]" value="A" readonly>
                                </div>
                                <div class="col-3">
                                    <input type="number" class="form-control form-control-sm" name="grading_scale[A][min]" value="90" min="0" max="100">
                                </div>
                                <div class="col-3">
                                    <input type="number" class="form-control form-control-sm" name="grading_scale[A][max]" value="100" min="0" max="100">
                                </div>
                                <div class="col-3">
                                    <input type="number" class="form-control form-control-sm" name="grading_scale[A][gpa]" value="1.0" step="0.1" min="0" max="4.0">
                            </div>
                            <!-- Add more grade scales as needed -->
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-success">Save Academic Configuration</button>
            </form>
        </section>
    </main>

    <div class="dark-mode-toggle" onclick="toggleDarkMode()">
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

            // Load current settings
            loadSettings();
        });

        async function loadSettings() {
            try {
                const response = await fetch('../api/settings_api.php?action=load');
                const data = await response.json();

                if (data.ok && data.settings) {
                    const settings = data.settings;

                    // Populate general settings
                    document.querySelector('#generalSettings input[name="school_name"]').value = settings.school_name || '';
                    document.querySelector('#generalSettings input[name="school_email"]').value = settings.school_email || '';
                    document.querySelector('#generalSettings input[name="school_address"]').value = settings.school_address || '';
                    document.querySelector('#generalSettings input[name="school_phone"]').value = settings.school_phone || '';

                    // Populate security settings
                    document.querySelector('#securitySettings select').value = settings.require_2fa || 'optional';
                    document.querySelector('#securitySettings input[name="session_timeout"]').value = settings.session_timeout || 30;
                    document.querySelector('#securitySettings input[name="password_min_length"]').value = settings.password_min_length || 8;

                    // Populate academic settings
                    document.querySelector('#academicSettings input[name="academic_year"]').value = settings.academic_year || '';
                    document.querySelector('#academicSettings select').value = settings.current_semester || '1';
                    document.querySelector('#academicSettings input[name="enrollment_start"]').value = settings.enrollment_start || '';
                    document.querySelector('#academicSettings input[name="enrollment_end"]').value = settings.enrollment_end || '';
                }
            } catch (error) {
                console.error('Error loading settings:', error);
            }
        }

        document.getElementById('generalSettings').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'save');

            fetch('../api/settings_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.ok) {
                    alert('General settings saved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                alert('Error: ' + error);
            });
        });

        document.getElementById('securitySettings').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'save');

            fetch('../api/settings_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.ok) {
                    alert('Security settings saved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                alert('Error: ' + error);
            });
        });

        document.getElementById('academicSettings').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'save');

            fetch('../api/settings_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.ok) {
                    alert('Academic settings saved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                alert('Error: ' + error);
            });
        });

        document.getElementById('academicConfigSettings').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'save');

            fetch('../api/settings_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.ok) {
                    alert('Academic configuration saved successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                alert('Error: ' + error);
            });
        });
    </script>
</body>
</html>
