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

// Sample documents data
$documents = [
    'academic' => [
        ['name' => 'Report Card (1st Quarter)', 'date' => '2024-01-15', 'size' => '2.3 MB', 'type' => 'PDF'],
        ['name' => 'Report Card (2nd Quarter)', 'date' => '2024-03-15', 'size' => '2.1 MB', 'type' => 'PDF'],
        ['name' => 'Certificate of Enrollment', 'date' => '2024-01-10', 'size' => '1.8 MB', 'type' => 'PDF'],
        ['name' => 'Transcript of Records', 'date' => '2024-01-05', 'size' => '3.2 MB', 'type' => 'PDF'],
    ],
    'forms' => [
        ['name' => 'Student Information Sheet', 'date' => '2024-01-01', 'size' => '1.2 MB', 'type' => 'PDF'],
        ['name' => 'Parent Consent Form', 'date' => '2024-01-01', 'size' => '0.8 MB', 'type' => 'PDF'],
        ['name' => 'Medical Certificate Template', 'date' => '2024-01-01', 'size' => '0.5 MB', 'type' => 'PDF'],
        ['name' => 'Excuse Letter Template', 'date' => '2024-01-01', 'size' => '0.3 MB', 'type' => 'PDF'],
    ],
    'certificates' => [
        ['name' => 'Good Moral Certificate', 'date' => '2024-02-01', 'size' => '1.5 MB', 'type' => 'PDF'],
        ['name' => 'Honor Roll Certificate', 'date' => '2024-03-20', 'size' => '2.0 MB', 'type' => 'PDF'],
        ['name' => 'Participation Certificate', 'date' => '2024-02-15', 'size' => '1.8 MB', 'type' => 'PDF'],
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Documents - St. Luke's School</title>
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
                <span class="topbar-subtitle">Documents</span>
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
        <!-- Document Categories -->
        <section class="card">
            <h2>📄 My Documents</h2>
            <p class="text-muted">Download your official documents and forms from St. Luke's School.</p>
            
            <!-- Search and Filter -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <input type="text" class="form-control" id="documentSearch" placeholder="Search documents...">
                </div>
                <div class="col-md-3">
                    <select class="form-control" id="categoryFilter">
                        <option value="all">All Categories</option>
                        <option value="academic">Academic Records</option>
                        <option value="forms">Forms & Templates</option>
                        <option value="certificates">Certificates</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-control" id="typeFilter">
                        <option value="all">All Types</option>
                        <option value="PDF">PDF</option>
                        <option value="DOC">DOC</option>
                        <option value="XLS">XLS</option>
                    </select>
                </div>
            </div>
        </section>

        <!-- Academic Records -->
        <section class="card">
            <h3>📚 Academic Records</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Document Name</th>
                            <th>Date Issued</th>
                            <th>File Size</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents['academic'] as $doc): ?>
                        <tr class="document-row" data-category="academic" data-type="<?= $doc['type'] ?>">
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="file-icon me-2">📄</span>
                                    <strong><?= htmlspecialchars($doc['name']) ?></strong>
                                </div>
                            </td>
                            <td><?= date('M j, Y', strtotime($doc['date'])) ?></td>
                            <td><?= $doc['size'] ?></td>
                            <td><span class="badge bg-primary"><?= $doc['type'] ?></span></td>
                            <td>
                                <button class="btn btn-primary btn-sm me-2" onclick="downloadDocument('<?= $doc['name'] ?>')">
                                    📥 Download
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="previewDocument('<?= $doc['name'] ?>')">
                                    👁️ Preview
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Forms & Templates -->
        <section class="card">
            <h3>📋 Forms & Templates</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Document Name</th>
                            <th>Date Added</th>
                            <th>File Size</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents['forms'] as $doc): ?>
                        <tr class="document-row" data-category="forms" data-type="<?= $doc['type'] ?>">
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="file-icon me-2">📋</span>
                                    <strong><?= htmlspecialchars($doc['name']) ?></strong>
                                </div>
                            </td>
                            <td><?= date('M j, Y', strtotime($doc['date'])) ?></td>
                            <td><?= $doc['size'] ?></td>
                            <td><span class="badge bg-success"><?= $doc['type'] ?></span></td>
                            <td>
                                <button class="btn btn-primary btn-sm me-2" onclick="downloadDocument('<?= $doc['name'] ?>')">
                                    📥 Download
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="previewDocument('<?= $doc['name'] ?>')">
                                    👁️ Preview
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Certificates -->
        <section class="card">
            <h3>🏆 Certificates</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Document Name</th>
                            <th>Date Issued</th>
                            <th>File Size</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents['certificates'] as $doc): ?>
                        <tr class="document-row" data-category="certificates" data-type="<?= $doc['type'] ?>">
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="file-icon me-2">🏆</span>
                                    <strong><?= htmlspecialchars($doc['name']) ?></strong>
                                </div>
                            </td>
                            <td><?= date('M j, Y', strtotime($doc['date'])) ?></td>
                            <td><?= $doc['size'] ?></td>
                            <td><span class="badge bg-warning"><?= $doc['type'] ?></span></td>
                            <td>
                                <button class="btn btn-primary btn-sm me-2" onclick="downloadDocument('<?= $doc['name'] ?>')">
                                    📥 Download
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="previewDocument('<?= $doc['name'] ?>')">
                                    👁️ Preview
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Document Request -->
        <section class="card">
            <h3>📝 Request New Document</h3>
            <p class="text-muted">Need a document that's not listed here? Request it from the registrar's office.</p>
            <form id="documentRequestForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="requestType" class="form-label">Document Type</label>
                            <select class="form-control" id="requestType" required>
                                <option value="">Select document type</option>
                                <option value="transcript">Official Transcript</option>
                                <option value="diploma">Diploma Copy</option>
                                <option value="enrollment">Certificate of Enrollment</option>
                                <option value="good_moral">Good Moral Certificate</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="urgency" class="form-label">Urgency</label>
                            <select class="form-control" id="urgency" required>
                                <option value="normal">Normal (3-5 business days)</option>
                                <option value="urgent">Urgent (1-2 business days)</option>
                                <option value="rush">Rush (Same day)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="requestNotes" class="form-label">Additional Notes</label>
                    <textarea class="form-control" id="requestNotes" rows="3" placeholder="Please provide any additional information about your request..."></textarea>
                </div>
                <button type="submit" class="btn btn-success">📤 Submit Request</button>
            </form>
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

        // Document search and filter functionality
        document.getElementById('documentSearch').addEventListener('input', filterDocuments);
        document.getElementById('categoryFilter').addEventListener('change', filterDocuments);
        document.getElementById('typeFilter').addEventListener('change', filterDocuments);

        function filterDocuments() {
            const searchTerm = document.getElementById('documentSearch').value.toLowerCase();
            const categoryFilter = document.getElementById('categoryFilter').value;
            const typeFilter = document.getElementById('typeFilter').value;
            
            const rows = document.querySelectorAll('.document-row');
            
            rows.forEach(row => {
                const documentName = row.querySelector('td:first-child').textContent.toLowerCase();
                const category = row.dataset.category;
                const type = row.dataset.type;
                
                const matchesSearch = documentName.includes(searchTerm);
                const matchesCategory = categoryFilter === 'all' || category === categoryFilter;
                const matchesType = typeFilter === 'all' || type === typeFilter;
                
                if (matchesSearch && matchesCategory && matchesType) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function downloadDocument(documentName) {
            if (confirm(`Download "${documentName}"?`)) {
                alert(`Downloading ${documentName}...`);
                // In a real application, this would trigger the actual download
            }
        }

        function previewDocument(documentName) {
            alert(`Opening preview for ${documentName}...`);
            // In a real application, this would open a preview modal or new window
        }

        // Document request form
        document.getElementById('documentRequestForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const requestType = document.getElementById('requestType').value;
            const urgency = document.getElementById('urgency').value;
            const notes = document.getElementById('requestNotes').value;
            
            if (confirm(`Submit request for ${requestType} with ${urgency} priority?`)) {
                alert('Document request submitted successfully! You will be notified when it\'s ready.');
                this.reset();
            }
        });
    </script>

    <style>
        .file-icon {
            font-size: 1.2rem;
        }
        
        .document-row:hover {
            background-color: #f8f9fa;
        }
        
        /* Dark mode styles */
        .dark-mode .document-row:hover {
            background-color: #333 !important;
        }
    </style>
</body>
</html>
