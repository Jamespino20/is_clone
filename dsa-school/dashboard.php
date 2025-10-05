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
           (isset($n['is_system']) && $n['is_system'] && 
            (empty($n['target_roles']) || in_array($user['role'], $n['target_roles'])));
});
$unreadNotifications = array_filter($userNotifications, fn($n) => !$n['read']);
$recentActivities = array_slice(array_filter($dsManager->getActivityStack()->getAll(), fn($a) => $a['user_email'] === $email), 0, 5);
?>
<?php
// Helper function to get user-friendly activity names
function get_activity_display_name($action) {
    $displayNames = [
        'login' => 'Logged in',
        'logout' => 'Logged out',
        'profile_view' => 'Viewed profile',
        'profile_update' => 'Updated profile',
        'security_check' => 'Checked security settings',
        'dashboard_access' => 'Accessed dashboard',
        'notification_sent' => 'Sent notification',
        'data_export' => 'Exported data',
        'settings_updated' => 'Updated system settings',
        'student_added' => 'Added new student',
        'student_updated' => 'Updated student information',
        'grade_recorded' => 'Recorded grade',
        'attendance_marked' => 'Marked attendance',
        'payment_processed' => 'Processed payment',
        'document_requested' => 'Requested document',
        'evaluation_submitted' => 'Submitted evaluation',
        'assignment_submitted' => 'Submitted assignment',
        'class_created' => 'Created class',
        'section_assigned' => 'Assigned to section',
        'report_generated' => 'Generated report',
        'submit_evaluation' => 'Submitted evaluation',
        'view_grades' => 'Viewed grades',
        'view_attendance' => 'Viewed attendance',
        'view_tuition' => 'Viewed tuition balance',
        'view_documents' => 'Viewed documents'
    ];

    return $displayNames[$action] ?? ucfirst(str_replace('_', ' ', $action));
}
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
  <?php $subtitle = 'Information System'; $assetPrefix = ''; include __DIR__ . '/partials/header.php'; ?>

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
          <?php
          $allUsers = read_users();
          
          $studentsFile = __DIR__ . '/api/data/students.json';
          $allStudentsData = [];
          if (file_exists($studentsFile)) {
            $studentsRaw = @file_get_contents($studentsFile);
            $allStudentsData = json_decode($studentsRaw ?: '[]', true) ?: [];
          }
          
          $studentEmails = [];
          foreach ($allStudentsData as $s) {
            $studentEmails[strtolower($s['email'] ?? '')] = true;
          }
          foreach ($allUsers as $u) {
            if (($u['role'] ?? '') === 'Student') {
              $e = strtolower($u['email'] ?? '');
              if (!isset($studentEmails[$e])) {
                $allStudentsData[] = $u;
              }
            }
          }
          $totalStudents = count($allStudentsData);
          ?>
          <div class="stat-item">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
              <h3><?= count($allUsers) ?></h3>
              <p>Total Users</p>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon">👨‍🎓</div>
            <div class="stat-content">
              <h3><?= $totalStudents ?></h3>
              <p>Total Students</p>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon">📄</div>
            <div class="stat-content">
              <h3><?= $dsManager->getDocumentRequestQueue()->size() ?></h3>
              <p>Document Requests</p>
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
            <div class="stat-icon">⭐</div>
            <div class="stat-content">
              <h3><?= $dsManager->getEvaluationResponseStack()->size() ?></h3>
              <p>Eval Responses</p>
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
          <?php
          $allStudents = [];
          $studentsFile = __DIR__ . '/api/data/students.json';
          if (file_exists($studentsFile)) {
            $studentsRaw = @file_get_contents($studentsFile);
            $allStudents = json_decode($studentsRaw ?: '[]', true) ?: [];
          }
          
          $allUsers = read_users();
          $studentEmails = [];
          foreach ($allStudents as $s) {
            $studentEmails[strtolower($s['email'] ?? '')] = true;
          }
          foreach ($allUsers as $u) {
            if (($u['role'] ?? '') === 'Student') {
              $e = strtolower($u['email'] ?? '');
              if (!isset($studentEmails[$e])) {
                $allStudents[] = [
                  'email' => $u['email'] ?? '',
                  'enrollment_status' => 'Enrolled',
                  'tuition_balance' => 0,
                ];
              }
            }
          }

          $enrolledStudents = array_filter($allStudents, fn($s) => ($s['enrollment_status'] ?? '') === 'Enrolled');
          $totalBalance = array_sum(array_column($allStudents, 'tuition_balance'));

          $attendanceFile = __DIR__ . '/api/data/attendance.json';
          $attendanceRecords = [];
          if (file_exists($attendanceFile)) {
            $attendanceRaw = @file_get_contents($attendanceFile);
            $attendanceRecords = json_decode($attendanceRaw ?: '[]', true) ?: [];
          }
          
          $today = date('Y-m-d');
          $todayRecs = array_filter($attendanceRecords, fn($r) => (($r['date'] ?? '') === $today));
          $totalStudents = 0; $present = 0; $late = 0;
          foreach ($todayRecs as $rec) {
            $totalStudents++;
            $status = strtolower($rec['status'] ?? '');
            if ($status === 'present') $present++;
            if ($status === 'late') $late++;
          }
          $attendanceRate = $totalStudents ? round((($present + $late) / $totalStudents) * 100, 1) : 0;

          $userNotifCount = count($userNotifications);
          ?>
          <div class="stat-item">
            <div class="stat-icon">👨‍🎓</div>
            <div class="stat-content">
              <h3><?= count($enrolledStudents) ?></h3>
              <p>Active Students</p>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
              <h3>₱<?= number_format($totalBalance, 0) ?></h3>
              <p>Total Outstanding</p>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon">📅</div>
            <div class="stat-content">
              <h3><?= $attendanceRate ?>%</h3>
              <p>Today's Attendance</p>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon">📢</div>
            <div class="stat-content">
              <h3><?= $userNotifCount ?></h3>
              <p>My Notifications</p>
            </div>
          </div>
        <?php elseif ($userRole === 'Faculty'): ?>
          <?php
          $gradesFile = __DIR__ . '/api/data/grades.json';
          $facultyClasses = [];
          if (file_exists($gradesFile)) {
            $gradesRaw = @file_get_contents($gradesFile);
            $gradesData = json_decode($gradesRaw ?: '[]', true) ?: [];
            $classNames = [];
            foreach ($gradesData as $grade) {
              if (isset($grade['class'])) {
                $classNames[$grade['class']] = true;
              }
            }
            $facultyClasses = array_keys($classNames);
          }
          
          if (empty($facultyClasses)) {
            $enrollmentFile = __DIR__ . '/api/data/enrollment.json';
            if (file_exists($enrollmentFile)) {
              $enrollmentRaw = @file_get_contents($enrollmentFile);
              $enrollmentData = json_decode($enrollmentRaw ?: '{}', true) ?: [];
              $yearLevels = $enrollmentData['yearLevels'] ?? [];
              $facultyClasses = count($yearLevels) > 0 ? $yearLevels : ['10'];
            } else {
              $facultyClasses = ['10'];
            }
          }

          $studentsFile = __DIR__ . '/api/data/students.json';
          $facultyStudents = [];
          if (file_exists($studentsFile)) {
            $studentsRaw = @file_get_contents($studentsFile);
            $allStudents = json_decode($studentsRaw ?: '[]', true) ?: [];
            $facultyStudents = array_filter($allStudents, function($s) {
              $grade = $s['grade_level'] ?? '';
              return !empty($grade);
            });
          }

          $evaluationResponsesFile = __DIR__ . '/api/data/evaluation_responses.json';
          $facultyEvaluations = [];
          if (file_exists($evaluationResponsesFile)) {
            $evalRaw = @file_get_contents($evaluationResponsesFile);
            $allEvaluations = json_decode($evalRaw ?: '[]', true) ?: [];
            $facultyEvaluations = array_filter($allEvaluations, function($e) use ($user) {
              return strtolower($e['teacher_email'] ?? '') === strtolower($user['email']);
            });
          }

          $totalRating = 0;
          $evalCount = count($facultyEvaluations);
          if ($evalCount > 0) {
            foreach ($facultyEvaluations as $eval) {
              $scores = $eval['scores'] ?? [];
              if (!empty($scores)) {
                $totalRating += array_sum($scores) / count($scores);
              }
            }
            $avgRating = round($totalRating / $evalCount, 1);
          } else {
            $avgRating = 0;
          }

          $pendingGrades = 0;
          ?>
          <div class="stat-item">
            <div class="stat-icon">📚</div>
            <div class="stat-content">
              <h3><?= count($facultyClasses) ?></h3>
              <p>Active Classes</p>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
              <h3><?= count($facultyStudents) ?></h3>
              <p>Total Students</p>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon">📝</div>
            <div class="stat-content">
              <h3><?= $pendingGrades ?></h3>
              <p>Pending Grades</p>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon">⭐</div>
            <div class="stat-content">
              <h3><?= $avgRating ?></h3>
              <p>Avg Rating</p>
            </div>
          </div>
        <?php elseif ($userRole === 'Student'): ?>
          <?php
          $studentData = [];
          $enrolledCourses = [];
          $studentAttendance = ['rate' => 0, 'balance' => 0];
          $currentGPA = 0;

          $studentsFile = __DIR__ . '/api/data/students.json';
          if (file_exists($studentsFile)) {
            $studentsRaw = @file_get_contents($studentsFile);
            $allStudents = json_decode($studentsRaw ?: '[]', true) ?: [];
            $studentMatches = array_filter($allStudents, function($s) use ($user) {
              return strtolower($s['email'] ?? '') === strtolower($user['email']);
            });
            $studentData = reset($studentMatches) ?: [];

            if ($studentData) {
              $gradesFile = __DIR__ . '/api/data/grades.json';
              if (file_exists($gradesFile)) {
                $gradesRaw = @file_get_contents($gradesFile);
                $gradesData = json_decode($gradesRaw ?: '[]', true) ?: [];
                $studentCourses = [];
                foreach ($gradesData as $grade) {
                  $studentId = $studentData['student_id'] ?? '';
                  if (($grade['student_id'] ?? '') === $studentId && !empty($grade['subject'] ?? '')) {
                    $studentCourses[$grade['subject']] = true;
                  }
                }
                $enrolledCourses = array_keys($studentCourses);
              }

              if (empty($enrolledCourses)) {
                $gradeLevel = $studentData['grade_level'] ?? '';
                if ($gradeLevel) {
                  $baseCourses = 6;
                  if (preg_match('/Grade (\d+)/', $gradeLevel, $matches)) {
                    $gradeNum = (int)$matches[1];
                    if ($gradeNum >= 7) $baseCourses = 8;
                  }
                  $enrolledCourses = range(1, $baseCourses);
                }
              }

              $studentAttendance = [
                'rate' => $studentData['attendance_rate'] ?? 0,
                'balance' => $studentData['tuition_balance'] ?? 0
              ];
              $currentGPA = $studentData['gpa'] ?? 0;
            }
          }
          ?>
          <div class="stat-item">
            <div class="stat-icon">📖</div>
            <div class="stat-content">
              <h3><?= count($enrolledCourses) ?></h3>
              <p>Enrolled Courses</p>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
              <h3><?= number_format($currentGPA, 1) ?></h3>
              <p>Current GPA</p>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon">📅</div>
            <div class="stat-content">
              <h3><?= $studentAttendance['rate'] ?>%</h3>
              <p>Attendance</p>
            </div>
          </div>
          <div class="stat-item">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
              <h3>₱<?= number_format($studentAttendance['balance'], 0) ?></h3>
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
            <span class="activity-icon">
              <?php
              $actionIcons = [
                'login' => '🔑',
                'logout' => '🚪',
                'profile_view' => '👤',
                'profile_update' => '✏️',
                'security_check' => '🔒',
                'dashboard_access' => '📊',
                'notification_sent' => '📢',
                'data_export' => '📤',
                'settings_updated' => '⚙️',
                'student_added' => '👨‍🎓',
                'student_updated' => '✏️',
                'grade_recorded' => '📝',
                'attendance_marked' => '📅',
                'payment_processed' => '💰',
                'document_requested' => '📄',
                'evaluation_submitted' => '⭐',
                'assignment_submitted' => '📋',
                'class_created' => '🏫',
                'section_assigned' => '📋',
                'report_generated' => '📊'
              ];
              $icon = $actionIcons[$activity['action']] ?? '🔍';
              echo $icon;
              ?>
            </span>
            <div class="activity-content">
              <p><strong><?= htmlspecialchars(get_activity_display_name($activity['action'])) ?></strong></p>
              <?php if ($activity['details']): ?>
                <p class="text-muted"><?= htmlspecialchars($activity['details']) ?></p>
              <?php endif; ?>
              <small class="text-muted">
                <?php
                $timeAgo = time() - $activity['timestamp'];
                if ($timeAgo < 60) {
                  echo 'Just now';
                } elseif ($timeAgo < 3600) {
                  echo floor($timeAgo / 60) . ' minutes ago';
                } elseif ($timeAgo < 86400) {
                  echo floor($timeAgo / 3600) . ' hours ago';
                } else {
                  echo floor($timeAgo / 86400) . ' days ago';
                }
                ?>
              </small>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
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
    // Minimal fallback if global-search.js not loaded
    function performSearch(){ /* handled by global-search.js live results */ }
    
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
  <script src="assets/js/global-search.js"></script>
</body>
</html>
