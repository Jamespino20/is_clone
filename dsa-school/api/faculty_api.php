<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');

$email = $_SESSION['user_email'] ?? null;
if (!$email) { echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit; }

$user = get_user_by_email($email);
if (!$user) { echo json_encode(['ok'=>false,'error'=>'User not found']); exit; }

$file = __DIR__ . '/data/faculty_assignments.json';
if (!file_exists($file)) file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));

function read_faculty_assignments(string $f): array {
    $raw = @file_get_contents($f);
    $j = json_decode($raw ?: '[]', true);
    return is_array($j) ? $j : [];
}

function write_faculty_assignments(string $f, array $list): void {
    @file_put_contents($f, json_encode($list, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$assignments = read_faculty_assignments($file);

switch ($action) {
    case 'list_for_student':
        if ($user['role'] !== 'Student') {
            echo json_encode(['ok'=>false,'error'=>'Only students can view their teachers']);
            break;
        }
        
        $studentEmail = strtolower($email);
        $teachers = [];
        
        foreach ($assignments as $assignment) {
            $assignedEmail = strtolower($assignment['student_email'] ?? '');
            if ($assignedEmail === $studentEmail) {
                $teacherEmail = $assignment['faculty_email'] ?? '';
                $teacher = get_user_by_email($teacherEmail);
                
                if ($teacher && $teacher['role'] === 'Faculty') {
                    $teachers[] = [
                        'id' => $assignment['id'] ?? 0,
                        'name' => $teacher['name'] ?? 'Unknown',
                        'email' => $teacher['email'] ?? '',
                        'subject' => $assignment['subject'] ?? 'Subject',
                        'evaluated' => $assignment['evaluated'] ?? false
                    ];
                }
            }
        }
        
        echo json_encode(['ok'=>true,'teachers'=>$teachers]);
        break;
        
    case 'mark_evaluated':
        if ($user['role'] !== 'Student') {
            echo json_encode(['ok'=>false,'error'=>'Only students can mark evaluations']);
            break;
        }
        
        $teacherId = intval($_POST['teacher_id'] ?? 0);
        if (!$teacherId) {
            echo json_encode(['ok'=>false,'error'=>'Missing teacher ID']);
            break;
        }
        
        $studentEmail = strtolower($email);
        $found = false;
        
        foreach ($assignments as &$assignment) {
            if (intval($assignment['id'] ?? 0) === $teacherId && 
                strtolower($assignment['student_email'] ?? '') === $studentEmail) {
                $assignment['evaluated'] = true;
                $found = true;
                break;
            }
        }
        unset($assignment);
        
        if ($found) {
            write_faculty_assignments($file, $assignments);
            echo json_encode(['ok'=>true]);
        } else {
            echo json_encode(['ok'=>false,'error'=>'Assignment not found']);
        }
        break;
        
    case 'assign':
        if (!in_array($user['role'], ['Staff', 'Administrator'], true)) {
            echo json_encode(['ok'=>false,'error'=>'Forbidden']);
            break;
        }
        
        $facultyEmail = trim($_POST['faculty_email'] ?? '');
        $studentEmail = trim($_POST['student_email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        
        if (!$facultyEmail || !$studentEmail || !$subject) {
            echo json_encode(['ok'=>false,'error'=>'Missing required fields']);
            break;
        }
        
        $ids = array_column($assignments, 'id');
        $maxId = $ids ? max($ids) : 0;
        
        $newAssignment = [
            'id' => $maxId + 1,
            'faculty_email' => strtolower($facultyEmail),
            'student_email' => strtolower($studentEmail),
            'subject' => $subject,
            'evaluated' => false,
            'created' => time()
        ];
        
        $assignments[] = $newAssignment;
        write_faculty_assignments($file, $assignments);
        echo json_encode(['ok'=>true,'item'=>$newAssignment]);
        break;
    
    case 'my_classes':
        if ($user['role'] !== 'Faculty') {
            echo json_encode(['ok'=>false,'error'=>'Only faculty can view their classes']);
            break;
        }
        
        $facultyEmail = strtolower($email);
        $classes = [];
        
        foreach ($assignments as $assignment) {
            $assignedFaculty = strtolower($assignment['faculty_email'] ?? '');
            if ($assignedFaculty === $facultyEmail) {
                $subject = $assignment['subject'] ?? 'Unknown Subject';
                $studentEmail = $assignment['student_email'] ?? '';
                
                if (!isset($classes[$subject])) {
                    $classes[$subject] = [
                        'subject' => $subject,
                        'students' => [],
                        'student_count' => 0
                    ];
                }
                
                $student = get_user_by_email($studentEmail);
                if ($student && $student['role'] === 'Student') {
                    $classes[$subject]['students'][] = [
                        'email' => $student['email'] ?? '',
                        'name' => $student['name'] ?? 'Unknown',
                        'student_id' => $student['student_id'] ?? '',
                        'grade_level' => $student['grade_level'] ?? ''
                    ];
                    $classes[$subject]['student_count']++;
                }
            }
        }
        
        echo json_encode(['ok'=>true,'classes'=>array_values($classes)]);
        break;
        
    default:
        echo json_encode(['ok'=>false,'error'=>'Unknown action']);
}
