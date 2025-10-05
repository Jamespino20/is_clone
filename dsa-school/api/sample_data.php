<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/data_structures.php';

header('Content-Type: application/json');

$email = $_SESSION['user_email'] ?? null;
if (!$email) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = get_user_by_email($email);
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'User not found']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add_sample_data':
        $dsManager = DataStructuresManager::getInstance();
        
        // Add sample notifications
        $dsManager->addNotification($email, 'Welcome to the System!', 'Your account is ready to use. Explore all the features available to you.', 'success');
        $dsManager->addNotification($email, 'System Update Available', 'New features have been added to your dashboard. Check out the latest improvements.', 'info');
        $dsManager->addNotification($email, 'Profile Update Reminder', 'Please complete your profile information to get the most out of the system.', 'warning');
        $dsManager->addNotification($email, 'Security Alert', 'Your account security is up to date. Keep your 2FA enabled for maximum protection.', 'info');
        
        // Add system-wide notifications
        $dsManager->addSystemNotification('System Maintenance Scheduled', 'The system will undergo maintenance on Sunday, 2:00 AM - 4:00 AM. Please save your work.', 'warning');
        $dsManager->addSystemNotification('New Features Available', 'Check out the new attendance tracking and document management features!', 'info', ['Student', 'Faculty']);
        $dsManager->addSystemNotification('Academic Calendar Updated', 'The academic calendar for next semester has been published. Check your schedules.', 'info', ['Student', 'Faculty', 'Staff']);
        
        // Add sample activities
        $dsManager->logActivity($email, 'profile_view', 'Viewed profile settings');
        $dsManager->logActivity($email, 'security_check', 'Checked security settings');
        $dsManager->logActivity($email, 'dashboard_access', 'Accessed main dashboard');
        $dsManager->logActivity($email, 'notification_sent', 'Sent notification to students');
        $dsManager->logActivity($email, 'data_export', 'Exported audit logs');
        
        // Add sample grades (if user is faculty or admin)
        if (in_array($user['role'], ['Faculty', 'Administrator'])) {
            $dsManager->recordGrade('student1@example.com', 'Mathematics', 85.5, 'First Semester');
            $dsManager->recordGrade('student2@example.com', 'Science', 92.0, 'First Semester');
            $dsManager->recordGrade('student3@example.com', 'English', 78.5, 'First Semester');
        }
        
        // Add sample assignments (if user is faculty or admin)
        if (in_array($user['role'], ['Faculty', 'Administrator'])) {
            $dsManager->submitAssignment('student1@example.com', 'MATH101_ASSIGNMENT1', 'Completed mathematics homework');
            $dsManager->submitAssignment('student2@example.com', 'SCI101_LAB_REPORT', 'Submitted science lab report');
        }
        
        // Add sample payments (if user is staff or admin)
        if (in_array($user['role'], ['Staff', 'Administrator'])) {
            $dsManager->addPayment('student1@example.com', 5000.00, 'Bank Transfer');
            $dsManager->addPayment('student2@example.com', 7500.00, 'Credit Card');
            $dsManager->addPayment('student3@example.com', 3000.00, 'Cash');
        }
        
        echo json_encode(['success' => true, 'message' => 'Sample data added successfully']);
        break;
        
    case 'index':
        // Build search index for global search
        $index = [];

        // Add navigation items
        $index['navigation'] = [
            ['label' => 'Dashboard', 'href' => 'dashboard.php', 'category' => 'Main'],
            ['label' => 'Profile', 'href' => 'profile.php', 'category' => 'Account'],
            ['label' => 'Security', 'href' => 'security.php', 'category' => 'Account'],
            ['label' => 'Notifications', 'href' => 'notifications.php', 'category' => 'Communication'],
        ];

        // Add admin items if user is admin
        if ($user['role'] === 'Administrator') {
            $index['admin'] = [
                ['label' => 'User Management', 'href' => 'admin/users.php', 'category' => 'Administration'],
                ['label' => 'System Settings', 'href' => 'admin/settings.php', 'category' => 'Administration'],
                ['label' => 'Reports', 'href' => 'admin/reports.php', 'category' => 'Administration'],
                ['label' => 'Audit Logs', 'href' => 'admin/audit.php', 'category' => 'Administration'],
                ['label' => 'System Backup', 'href' => 'admin/backup.php', 'category' => 'Administration'],
                ['label' => 'Admin Notifications', 'href' => 'admin/notifications.php', 'category' => 'Communication'],
            ];
        }

        // Add staff items if user is staff
        if ($user['role'] === 'Staff') {
            $index['staff'] = [
                ['label' => 'Student Management', 'href' => 'staff/students.php', 'category' => 'Management'],
                ['label' => 'Tuition Management', 'href' => 'staff/tuition.php', 'category' => 'Finance'],
                ['label' => 'Attendance', 'href' => 'staff/attendance.php', 'category' => 'Academics'],
                ['label' => 'Reports', 'href' => 'staff/reports.php', 'category' => 'Administration'],
                ['label' => 'Send Notifications', 'href' => 'staff/notifications.php', 'category' => 'Communication'],
            ];
        }

        // Add faculty items if user is faculty
        if ($user['role'] === 'Faculty') {
            $index['faculty'] = [
                ['label' => 'My Classes', 'href' => 'faculty/classes.php', 'category' => 'Teaching'],
                ['label' => 'Gradebook', 'href' => 'faculty/grades.php', 'category' => 'Teaching'],
                ['label' => 'Assignments', 'href' => 'faculty/assignments.php', 'category' => 'Teaching'],
                ['label' => 'Class Attendance', 'href' => 'faculty/attendance.php', 'category' => 'Teaching'],
                ['label' => 'Evaluations', 'href' => 'faculty/evaluations.php', 'category' => 'Teaching'],
                ['label' => 'Class Materials', 'href' => 'faculty/materials.php', 'category' => 'Teaching'],
            ];
        }

        // Add student items if user is student
        if ($user['role'] === 'Student') {
            $index['student'] = [
                ['label' => 'My Courses', 'href' => 'student/courses.php', 'category' => 'Academics'],
                ['label' => 'My Grades', 'href' => 'student/grades.php', 'category' => 'Academics'],
                ['label' => 'Attendance', 'href' => 'student/attendance.php', 'category' => 'Academics'],
                ['label' => 'Tuition Balance', 'href' => 'student/tuition.php', 'category' => 'Finance'],
                ['label' => 'Teacher Evaluations', 'href' => 'student/evaluations.php', 'category' => 'Feedback'],
                ['label' => 'Documents', 'href' => 'student/documents.php', 'category' => 'Academics'],
            ];
        }

        // Add sample courses for search
        $index['courses'] = [
            ['label' => 'Mathematics', 'href' => 'faculty/grades.php', 'category' => 'Subject'],
            ['label' => 'Science', 'href' => 'faculty/grades.php', 'category' => 'Subject'],
            ['label' => 'English', 'href' => 'faculty/grades.php', 'category' => 'Subject'],
            ['label' => 'Filipino', 'href' => 'faculty/grades.php', 'category' => 'Subject'],
            ['label' => 'Social Studies', 'href' => 'faculty/grades.php', 'category' => 'Subject'],
            ['label' => 'Physical Education', 'href' => 'faculty/grades.php', 'category' => 'Subject'],
        ];

        // Add sample students for search (if staff or admin)
        if (in_array($user['role'], ['Staff', 'Administrator'])) {
            $studentsFile = __DIR__ . '/data/students.json';
            if (file_exists($studentsFile)) {
                $studentsRaw = @file_get_contents($studentsFile);
                $students = json_decode($studentsRaw ?: '[]', true) ?: [];
                $studentIndex = array_map(function($student) {
                    return [
                        'label' => $student['name'] . ' (' . $student['student_id'] . ')',
                        'href' => 'staff/students.php',
                        'category' => 'Student',
                        'id' => $student['id']
                    ];
                }, array_slice($students, 0, 20)); // Limit to first 20 for performance
                $index['students'] = $studentIndex;
            }
        }

        echo json_encode(['index' => $index]);
        break;
        $dsManager = DataStructuresManager::getInstance();
        $allNotifications = $dsManager->getNotificationQueue()->getAll();
        $userNotifications = array_filter($allNotifications, fn($n) => $n['user_email'] === $email);
        
        echo json_encode(['notifications' => array_reverse($userNotifications)]);
        break;
        
    case 'get_activities':
        $dsManager = DataStructuresManager::getInstance();
        $allActivities = $dsManager->getActivityStack()->getAll();
        $userActivities = array_filter($allActivities, fn($a) => $a['user_email'] === $email);
        
        echo json_encode(['activities' => array_slice($userActivities, 0, 10)]);
        break;
        
    case 'search':
        $query = $_GET['q'] ?? '';
        $filter = $_GET['filter'] ?? 'all';
        
        if (empty($query)) {
            echo json_encode(['error' => 'Search query required']);
            break;
        }
        
        $dsManager = DataStructuresManager::getInstance();
        $results = [];
        
        // Search in notifications (including system notifications)
        if ($filter === 'all' || $filter === 'notifications') {
            $allNotifications = $dsManager->getNotificationQueue()->getAll();
            $userNotifications = array_filter($allNotifications, function($n) use ($email, $user) {
                // Include personal notifications and system notifications for this user's role
                return $n['user_email'] === $email || 
                       (isset($n['is_system']) && $n['is_system'] && 
                        (empty($n['target_roles']) || in_array($user['role'], $n['target_roles'])));
            });
            $notificationResults = array_filter($userNotifications, function($n) use ($query) {
                return stripos($n['title'], $query) !== false || stripos($n['message'], $query) !== false;
            });
            $results['notifications'] = array_values($notificationResults);
        }
        
        // Search in activities
        if ($filter === 'all' || $filter === 'activities') {
            $allActivities = $dsManager->getActivityStack()->getAll();
            $userActivities = array_filter($allActivities, fn($a) => $a['user_email'] === $email);
            $activityResults = array_filter($userActivities, function($a) use ($query) {
                return stripos($a['action'], $query) !== false || stripos($a['details'], $query) !== false;
            });
            $results['activities'] = array_values($activityResults);
        }
        
        // Search in users (if admin)
        if (($filter === 'all' || $filter === 'users') && $user['role'] === 'Administrator') {
            $allUsers = read_users();
            $userResults = array_filter($allUsers, function($u) use ($query) {
                return stripos($u['name'], $query) !== false || 
                       stripos($u['email'], $query) !== false ||
                       stripos($u['role'], $query) !== false;
            });
            $results['users'] = array_values($userResults);
        }
        
        // Search in courses (sample data)
        if ($filter === 'all' || $filter === 'courses') {
            $sampleCourses = [
                ['code' => 'MATH101', 'name' => 'Algebra I', 'instructor' => 'Dr. Maria Santos', 'credits' => 3],
                ['code' => 'ENG101', 'name' => 'English Composition', 'instructor' => 'Prof. John Cruz', 'credits' => 3],
                ['code' => 'SCI101', 'name' => 'General Science', 'instructor' => 'Dr. Ana Reyes', 'credits' => 4],
                ['code' => 'MATH201', 'name' => 'Calculus I', 'instructor' => 'Dr. Maria Santos', 'credits' => 4],
                ['code' => 'MATH301', 'name' => 'Statistics', 'instructor' => 'Dr. Maria Santos', 'credits' => 3]
            ];
            
            $courseResults = array_filter($sampleCourses, function($c) use ($query) {
                return stripos($c['code'], $query) !== false || 
                       stripos($c['name'], $query) !== false ||
                       stripos($c['instructor'], $query) !== false;
            });
            $results['courses'] = array_values($courseResults);
        }
        
        // Calculate total results
        $totalResults = count($results['notifications']) + 
                       count($results['activities']) + 
                       count($results['users']) + 
                       count($results['courses']);
        
        echo json_encode([
            'results' => $results, 
            'query' => $query, 
            'filter' => $filter,
            'total' => $totalResults
        ]);
        break;
        
    case 'mark_notification_read':
        $notificationId = $_POST['notification_id'] ?? '';
        
        // In a real system, you'd update the database
        // For now, we'll just return success
        echo json_encode(['success' => true]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>
