<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');

$email = $_SESSION['user_email'] ?? null;
if (!$email) { 
    echo json_encode(['ok'=>false,'error'=>'Unauthorized']); 
    exit; 
}

$user = get_user_by_email($email);
if (!$user || $user['role'] !== 'Student') { 
    echo json_encode(['ok'=>false,'error'=>'Forbidden']); 
    exit; 
}

$action = $_GET['action'] ?? 'profile';

// Helper functions
function read_json_file(string $file): array {
    if (!file_exists($file)) return [];
    $raw = @file_get_contents($file);
    $data = json_decode($raw ?: '[]', true);
    return is_array($data) ? $data : [];
}

switch ($action) {
    case 'profile':
        // Get student enrollment data
        $enrollmentFile = __DIR__ . '/data/enrollment.json';
        $enrollmentData = read_json_file($enrollmentFile);
        
        // Look for student assignment in enrollment data
        $studentAssignment = null;
        if (isset($enrollmentData['student_assignments'][$email])) {
            $studentAssignment = $enrollmentData['student_assignments'][$email];
        }
        
        echo json_encode([
            'ok' => true,
            'student' => [
                'name' => $user['name'],
                'email' => $email,
                'grade_level' => $studentAssignment['grade_level'] ?? null,
                'section' => $studentAssignment['section'] ?? null
            ]
        ]);
        break;
        
    case 'attendance':
        // Get attendance records for this student
        $attendanceFile = __DIR__ . '/data/attendance.json';
        $allAttendance = read_json_file($attendanceFile);
        
        // Filter by student email
        $studentAttendance = array_filter($allAttendance, function($record) use ($email) {
            return ($record['student_id'] ?? '') === $email;
        });
        
        echo json_encode([
            'ok' => true,
            'attendance' => array_values($studentAttendance)
        ]);
        break;
        
    case 'grades':
        // Get grades for this student
        $gradesFile = __DIR__ . '/data/grades.json';
        $allGrades = read_json_file($gradesFile);
        
        // Filter by exact email match for security
        $studentGrades = array_filter($allGrades, function($grade) use ($email) {
            return ($grade['student_email'] ?? '') === $email;
        });
        
        echo json_encode([
            'ok' => true,
            'grades' => array_values($studentGrades)
        ]);
        break;
        
    case 'courses':
        // Derive courses from grades data
        $gradesFile = __DIR__ . '/data/grades.json';
        $allGrades = read_json_file($gradesFile);
        
        // Filter by exact email match for security
        $studentGrades = array_filter($allGrades, function($grade) use ($email) {
            return ($grade['student_email'] ?? '') === $email;
        });
        
        $courses = [];
        $classNames = [];
        
        foreach ($studentGrades as $grade) {
            $className = $grade['class'] ?? '';
            if ($className && !in_array($className, $classNames)) {
                $classNames[] = $className;
                $courses[] = [
                    'class' => $className,
                    'faculty_email' => $grade['faculty_email'] ?? ''
                ];
            }
        }
        
        echo json_encode([
            'ok' => true,
            'courses' => $courses
        ]);
        break;
        
    default:
        echo json_encode(['ok'=>false,'error'=>'Unknown action']);
}
