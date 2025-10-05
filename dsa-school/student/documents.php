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
    <title>Documents - St. Luke's School</title>
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
        $subtitle = 'Documents'; $assetPrefix = '..'; include __DIR__ . '/../partials/header.php';
    ?>

    <main class="container">
        <section class="card">
            <h2>📄 My Documents</h2>
            <p class="text-muted">Download your official documents and forms from St. Luke's School.</p>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <input type="text" class="form-control" id="documentSearch" placeholder="Search documents...">
                </div>
                <div class="col-md-3">
                    <select class="form-control" id="categoryFilter" onchange="filterDocuments()">
                        <option value="all">All Categories</option>
                        <option value="academic">Academic Records</option>
                        <option value="forms">Forms & Templates</option>
                        <option value="certificates">Certificates</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-control" id="typeFilter" onchange="filterDocuments()">
                        <option value="all">All Types</option>
                        <option value="PDF">PDF</option>
                        <option value="DOC">DOC</option>
                        <option value="XLS">XLS</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="card">
            <h3>📚 Academic Records</h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Document Name</th>
                            <th>Date</th>
                            <th>Size</th>
                            <th>Type</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="academicDocs">
                        <tr><td colspan="5" class="text-center">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <h3>📋 Forms & Templates</h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Document Name</th>
                            <th>Date</th>
                            <th>Size</th>
                            <th>Type</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="formsDocs">
                        <tr><td colspan="5" class="text-center">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <h3>🏆 Certificates</h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Document Name</th>
                            <th>Date</th>
                            <th>Size</th>
                            <th>Type</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="certificatesDocs">
                        <tr><td colspan="5" class="text-center">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <h3>📩 Request a Document</h3>
            <p class="text-muted">Need a specific document? Request it here and we'll prepare it for you.</p>
            <button class="btn btn-primary" onclick="requestDocument()">+ Request Document</button>
        </section>
    </main>

    <div class="dark-mode-toggle" onclick="toggleDarkMode()">
        <span id="darkModeIcon">🌙</span>
    </div>

    <script>
        let allDocuments = { academic: [], forms: [], certificates: [] };

        async function loadDocuments() {
            try {
                const res = await fetch('../api/documents_api.php?action=list_available');
                const data = await res.json();
                if (data.ok && data.documents) {
                    allDocuments = data.documents;
                    renderDocuments();
                } else {
                    showError('academic');
                    showError('forms');
                    showError('certificates');
                }
            } catch (error) {
                console.error('Error loading documents:', error);
                showError('academic');
                showError('forms');
                showError('certificates');
            }
        }

        function renderDocuments() {
            renderCategory('academic', allDocuments.academic || []);
            renderCategory('forms', allDocuments.forms || []);
            renderCategory('certificates', allDocuments.certificates || []);
        }

        function renderCategory(category, docs) {
            const tbody = document.getElementById(category + 'Docs');
            if (!docs || docs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No documents available</td></tr>';
                return;
            }

            tbody.innerHTML = docs.map(doc => {
                const date = new Date(doc.date * 1000).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                return `
                    <tr class="doc-row" data-category="${category}" data-type="${doc.type || 'PDF'}">
                        <td><i class="bi bi-file-earmark-${doc.type?.toLowerCase() || 'pdf'}"></i> ${escapeHtml(doc.name || '')}</td>
                        <td>${date}</td>
                        <td>${escapeHtml(doc.size || 'N/A')}</td>
                        <td><span class="badge bg-info">${escapeHtml(doc.type || 'PDF')}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="downloadDocument('${escapeHtml(doc.name || '')}', '${doc.url || '#'}')">
                                📥 Download
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function showError(category) {
            const tbody = document.getElementById(category + 'Docs');
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No documents available</td></tr>';
        }

        function escapeHtml(text) {
            const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        function filterDocuments() {
            const categoryFilter = document.getElementById('categoryFilter').value;
            const typeFilter = document.getElementById('typeFilter').value;
            const searchTerm = document.getElementById('documentSearch').value.toLowerCase();

            document.querySelectorAll('.doc-row').forEach(row => {
                const category = row.dataset.category;
                const type = row.dataset.type;
                const text = row.textContent.toLowerCase();

                const matchesCategory = categoryFilter === 'all' || category === categoryFilter;
                const matchesType = typeFilter === 'all' || type === typeFilter;
                const matchesSearch = text.includes(searchTerm);

                row.style.display = (matchesCategory && matchesType && matchesSearch) ? '' : 'none';
            });
        }

        document.getElementById('documentSearch').addEventListener('input', filterDocuments);

        function downloadDocument(name, url) {
            if (url === '#' || !url) {
                showWarning('Document download link not available yet. Please contact the school office.');
                return;
            }
            window.open(url, '_blank');
        }

        async function requestDocument() {
            const type = prompt('What document do you need?\n\nExamples:\n- Certificate of Enrollment\n- Transcript of Records\n- Good Moral Certificate\n- Medical Certificate');
            if (!type) return;

            const notes = prompt('Any additional notes or specifications? (optional)') || '';

            try {
                const formData = new URLSearchParams({ type, notes });
                const res = await fetch('../api/documents_api.php?action=request', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: formData
                });

                const data = await res.json();
                if (data.ok) {
                    showSuccess('Document request submitted successfully! You will be notified when it\'s ready.');
                } else {
                    showError('Error: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                showError('Error submitting request: ' + error.message);
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
            loadDocuments();
        });
    </script>
</body>
</html>
