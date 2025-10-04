<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/api/helpers.php';
require_once __DIR__ . '/api/data_structures.php';

$email = $_SESSION['user_email'] ?? null;
if (!$email) {
    header('Location: index.php');
    exit;
}
$user = get_user_by_email($email);
if (!$user) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Define user role for dashboard display
$userRole = get_role_display_name($user['role']);

// Initialize data structures manager
$dsManager = DataStructuresManager::getInstance();

// Log dashboard access
$dsManager->logActivity($email, 'dashboard_access', 'Accessed main dashboard');

// Get user-specific data including system notifications
$allNotifications = $dsManager->getNotificationQueue()->getAll();
$userNotifications = array_filter($allNotifications, function($n) use ($email, $user) {
    // Include personal notifications and system notifications for this user's role
    return $n['user_email'] === $email || 
           (isset($n['is_system']) && $n['is_system'] && 
            (empty($n['target_roles']) || in_array($user['role'], $n['target_roles'])));
});
$unreadNotifications = array_filter($userNotifications, fn($n) => !$n['read']);
$recentActivities = array_slice(array_filter($dsManager->getActivityStack()->getAll(), fn($a) => $a['user_email'] === $email), 0, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
  <header class="topbar">
    <div class="topbar-left">
      <img src="assets/img/school-logo.png" alt="School Logo" class="topbar-logo">
      <div class="topbar-title">
        <h1>St. Luke's School of San Rafael</h1>
        <span class="topbar-subtitle">Information System</span>
      </div>
    </div>
    <div class="topbar-right">
      <div class="user-info">
        <span class="user-name">Welcome, <?= htmlspecialchars($user['name']) ?></span>
        <span class="user-role"><?= get_role_display_name($user['role']) ?></span>
      </div>
      <nav>
        <a href="profile.php" class="nav-link">Profile</a>
        <a href="security.php" class="nav-link">Security</a>
        <a href="notifications.php" class="nav-link">
          🔔 Notifications
          <?php if (count($unreadNotifications) > 0): ?>
            <span class="badge bg-warning"><?= count($unreadNotifications) ?></span>
          <?php endif; ?>
        </a>
        <?php if ($userRole === 'Administrator'): ?>
          <a href="audit_logs.php" class="nav-link">📋 Audit Logs</a>
        <?php endif; ?>
        <a href="api/logout.php" class="nav-link logout">Logout</a>
      </nav>
    </div>
  </header>

  <main class="container">
    <!-- Search Bar -->
    <section class="card search-section">
      <div class="search-container">
        <div class="search-input-group">
          <input type="text" id="globalSearch" class="search-input" placeholder="Search modules, courses, students, or any content...">
          <button class="search-btn" onclick="performSearch()">
            <span>🔍</span>
          </button>
        </div>
        <div class="search-filters">
          <select id="searchFilter" class="search-filter">
            <option value="all">All Content</option>
            <option value="courses">Courses</option>
            <option value="students">Students</option>
            <option value="grades">Grades</option>
            <option value="assignments">Assignments</option>
            <option value="reports">Reports</option>
          </select>
        </div>
      </div>
    </section>

    <!-- Quick Stats -->
    <section class="card">
      <h2>Quick Overview</h2>
      <div class="stats-grid">
        <?php if ($userRole === 'Administrator'): ?>
          <div class="stat-item">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
              <h3><?= count(read_users()) ?></h3>
              <p>Total Users</p>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
              <h3><?= $dsManager->getActivityStack()->size() ?></h3>
              <p>System Activities</p>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon">🔔</div>
            <div class="stat-content">
              <h3><?= $dsManager->getNotificationQueue()->size() ?></h3>
              <p>Total Notifications</p>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
              <h3><?= $dsManager->getPaymentQueue()->size() ?></h3>
              <p>Payment Records</p>
            </div>
          </div>
        <?php elseif ($userRole === 'Staff'): ?>
          <div class="stat-item">
            <div class="stat-icon">👨‍🎓</div>
            <div class="stat-content">
              <h3>245</h3>
              <p>Active Students</p>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
              <h3>₱1.2M</h3>
              <p>Tuition Collected</p>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon">📅</div>
            <div class="stat-content">
              <h3>92%</h3>
              <p>Avg Attendance</p>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon">📢</div>
            <div class="stat-content">
              <h3>15</h3>
              <p>Pending Notifications</p>
            </div>
          </div>
        <?php elseif ($userRole === 'Faculty'): ?>
          <div class="stat-item">
            <div class="stat-icon">📚</div>
            <div class="stat-content">
              <h3>3</h3>
              <p>Active Classes</p>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
              <h3>63</h3>
              <p>Total Students</p>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon">📝</div>
            <div class="stat-content">
              <h3>12</h3>
              <p>Pending Grades</p>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon">⭐</div>
            <div class="stat-content">
              <h3>4.8</h3>
              <p>Avg Rating</p>
            </div>
          </div>
        <?php elseif ($userRole === 'Student'): ?>
          <div class="stat-item">
            <div class="stat-icon">📖</div>
            <div class="stat-content">
              <h3>6</h3>
              <p>Enrolled Courses</p>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
              <h3>3.2</h3>
              <p>Current GPA</p>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon">📅</div>
            <div class="stat-content">
              <h3>85%</h3>
              <p>Attendance</p>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
              <h3>₱2,500</h3>
              <p>Tuition Balance</p>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <section class="card">
      <h2>Profile</h2>
      <p>Role: <strong><?= get_role_display_name($user['role']) ?></strong></p>
      <p>Email: <?= htmlspecialchars($user['email']) ?></p>
      <p>Sign-in Method: <strong>Email/Password</strong></p>
      <?php if (!empty($user['totp_secret'])): ?>
        <p><small class="text-muted">Two-Factor Authentication is enabled.</small></p>
      <?php endif; ?>
    </section>

    <section class="card">
      <h2>Quick Actions</h2>
      <div class="action-grid">
        <?php
        $userRole = get_role_display_name($user['role']);
        if ($userRole === 'Administrator'): ?>
          <a href="admin/users.php" class="action-card">
            <h3>👥 User Management</h3>
            <p>Manage students, faculty, and staff accounts</p>
          </a>
          <a href="admin/settings.php" class="action-card">
            <h3>⚙️ System Settings</h3>
            <p>Configure system preferences and security</p>
          </a>
          <a href="admin/reports.php" class="action-card">
            <h3>📊 Reports</h3>
            <p>View system analytics and reports</p>
          </a>
          <a href="admin/audit.php" class="action-card">
            <h3>📋 Audit Logs</h3>
            <p>View and export system audit logs</p>
          </a>
          <a href="admin/backup.php" class="action-card">
            <h3>💾 System Backup</h3>
            <p>Backup and restore system data</p>
          </a>
          <a href="admin/notifications.php" class="action-card">
            <h3>🔔 Notifications</h3>
            <p>Manage system-wide notifications</p>
          </a>
        <?php endif; ?>
        
        <?php if ($userRole === 'Staff'): ?>
          <a href="staff/students.php" class="action-card">
            <h3>👨‍🎓 Student Management</h3>
            <p>Manage student information and enrollment</p>
          </a>
          <a href="staff/tuition.php" class="action-card">
            <h3>💰 Tuition Management</h3>
            <p>Process payments and manage balances</p>
          </a>
          <a href="staff/attendance.php" class="action-card">
            <h3>📅 Attendance</h3>
            <p>Monitor and mark attendance</p>
          </a>
          <a href="staff/reports.php" class="action-card">
            <h3>📊 Reports</h3>
            <p>Generate student and teacher reports</p>
          </a>
          <a href="staff/notifications.php" class="action-card">
            <h3>📢 Send Notifications</h3>
            <p>Send messages to students and parents</p>
          </a>
        <?php endif; ?>
        
        <?php if ($userRole === 'Faculty'): ?>
          <a href="faculty/classes.php" class="action-card">
            <h3>📚 My Classes</h3>
            <p>Manage your classes and students</p>
          </a>
          <a href="faculty/grades.php" class="action-card">
            <h3>📝 Gradebook</h3>
            <p>Record and manage student grades</p>
          </a>
          <a href="faculty/assignments.php" class="action-card">
            <h3>📋 Assignments</h3>
            <p>Create and manage assignments</p>
          </a>
          <a href="faculty/attendance.php" class="action-card">
            <h3>📅 Attendance</h3>
            <p>Mark student and your own attendance</p>
          </a>
          <a href="faculty/evaluations.php" class="action-card">
            <h3>⭐ Evaluations</h3>
            <p>View student evaluation results</p>
          </a>
          <a href="faculty/materials.php" class="action-card">
            <h3>📁 Class Materials</h3>
            <p>Upload syllabi and course materials</p>
          </a>
        <?php endif; ?>
        
        <?php if ($userRole === 'Student'): ?>
          <a href="student/courses.php" class="action-card">
            <h3>📖 My Courses</h3>
            <p>View your enrolled courses and schedule</p>
          </a>
          <a href="student/grades.php" class="action-card">
            <h3>📊 My Grades</h3>
            <p>Check your academic progress (DepEd Form 137)</p>
          </a>
          <a href="student/attendance.php" class="action-card">
            <h3>📅 Attendance</h3>
            <p>Mark your attendance in subjects</p>
          </a>
          <a href="student/tuition.php" class="action-card">
            <h3>💰 Tuition Balance</h3>
            <p>View your tuition balance and payments</p>
          </a>
          <a href="student/evaluations.php" class="action-card">
            <h3>⭐ Teacher Evaluations</h3>
            <p>Evaluate your teachers (when available)</p>
          </a>
          <a href="student/documents.php" class="action-card">
            <h3>📄 Documents</h3>
            <p>Download official documents and forms</p>
          </a>
        <?php endif; ?>
        
        <a href="profile.php" class="action-card">
          <h3>👤 Profile</h3>
          <p>Update your personal information</p>
        </a>
        <a href="security.php" class="action-card">
          <h3>🔒 Security</h3>
          <p>Manage 2FA and security settings</p>
        </a>
      </div>
    </section>

    <section class="card">
      <h2>📊 Recent Activity</h2>
      <div class="activity-list">
        <?php if (empty($recentActivities)): ?>
          <div class="text-center text-muted py-3">
            <p>No recent activity to display.</p>
          </div>
        <?php else: ?>
          <?php foreach ($recentActivities as $activity): ?>
            <div class="activity-item">
              <span class="activity-icon">🔍</span>
              <div class="activity-content">
                <p><strong><?= htmlspecialchars($activity['action']) ?></strong></p>
                <?php if ($activity['details']): ?>
                  <p class="text-muted"><?= htmlspecialchars($activity['details']) ?></p>
                <?php endif; ?>
                <small class="text-muted"><?= date('M j, g:i A', $activity['timestamp']) ?></small>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>
      </div>
    </section>

    <!-- Notifications Panel -->
    <section class="card">
      <h2>🔔 Notifications</h2>
      <div class="notifications-list">
        <?php if (empty($userNotifications)): ?>
          <div class="text-center text-muted py-3">
            <p>No notifications yet</p>
            <small>You'll see important updates and messages here.</small>
          </div>
        <?php else: ?>
          <?php foreach (array_slice(array_reverse($userNotifications), 0, 3) as $notification): ?>
            <div class="notification-item <?= !$notification['read'] ? 'unread' : '' ?>">
              <span class="notification-icon">
                <?php
                $icons = [
                  'info' => 'ℹ️',
                  'warning' => '⚠️',
                  'success' => '✅',
                  'error' => '❌',
                  'reminder' => '🔔'
                ];
                echo $icons[$notification['type']] ?? '📢';
                ?>
              </span>
              <div class="notification-content">
                <p><strong><?= htmlspecialchars($notification['title']) ?></strong></p>
                <p><?= htmlspecialchars($notification['message']) ?></p>
                <small class="text-muted"><?= date('M j, g:i A', $notification['timestamp']) ?></small>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <div class="text-center mt-3">
        <a href="notifications.php" class="btn btn-outline-primary btn-sm">View All Notifications</a>
      </div>
    </section>

    <!-- Sample Data Section -->
    <section class="card">
      <h2>🧪 Development Tools</h2>
      <p>Add sample data to test the system functionality:</p>
      <div class="d-flex gap-2">
        <button onclick="addSampleData()" class="btn btn-success">Add Sample Data</button>
        <button onclick="refreshData()" class="btn btn-outline-primary">Refresh Data</button>
      </div>
    </section>
  </main>

  <!-- Dark Mode Toggle -->
  <div class="dark-mode-toggle" onClick="toggleDarkMode()">
    <span id="darkModeIcon">🌙</span>
  </div>

  <script>
    // Search functionality
    function performSearch() {
      const query = document.getElementById('globalSearch').value.trim();
      const filter = document.getElementById('searchFilter').value;
      
      if (!query) {
        alert('Please enter a search term');
        return;
      }
      
      // Call backend search API and display results
      const url = `api/sample_data.php?action=search&q=${encodeURIComponent(query)}&filter=${encodeURIComponent(filter)}`;
      fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
          if (data.error) {
            alert('Search error: ' + data.error);
            return;
          }
          const res = data.results || {};
          const notifCount = (res.notifications || []).length;
          const activityCount = (res.activities || []).length;
          const userCount = (res.users || []).length;
          const courseCount = (res.courses || []).length;
          const total = data.total || 0;
          
          let resultMessage = `Search Results for "${query}" (${total} total):\n\n`;
          if (notifCount > 0) resultMessage += `📢 Notifications: ${notifCount}\n`;
          if (activityCount > 0) resultMessage += `📊 Activities: ${activityCount}\n`;
          if (userCount > 0) resultMessage += `👥 Users: ${userCount}\n`;
          if (courseCount > 0) resultMessage += `📚 Courses: ${courseCount}\n`;
          
          if (total === 0) {
            resultMessage += '\nNo results found. Try different keywords or check your spelling.';
          } else {
            resultMessage += '\nClick "View All Notifications" to see detailed results.';
          }
          
          alert(resultMessage);
          console.log('Search results:', data);
        })
        .catch(err => alert('Search failed: ' + err));
    }
    
    // Allow Enter key to trigger search
    document.getElementById('globalSearch').addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        performSearch();
      }
    });
    
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
    
    // Auto-logout functionality (30 minutes of inactivity)
    let inactivityTimer;
    const INACTIVITY_TIMEOUT = 30 * 60 * 1000; // 30 minutes
    
    function resetInactivityTimer() {
      clearTimeout(inactivityTimer);
      inactivityTimer = setTimeout(() => {
        if (confirm('You have been inactive for 30 minutes. Would you like to stay logged in?')) {
          resetInactivityTimer();
        } else {
          window.location.href = 'api/logout.php';
        }
      }, INACTIVITY_TIMEOUT);
    }
    
    // Reset timer on user activity
    ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
      document.addEventListener(event, resetInactivityTimer, true);
    });
    
    // Start the timer
    resetInactivityTimer();

    // Add sample data for testing
    function addSampleData() {
      fetch('api/sample_data.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=add_sample_data'
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Sample data added successfully! Refresh the page to see the changes.');
        } else {
          alert('Error: ' + data.error);
        }
      })
      .catch(error => {
        alert('Error: ' + error);
      });
    }

    // Refresh data
    function refreshData() {
      location.reload();
    }
  </script>
</body>
</html>
