<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');

$email = $_SESSION['user_email'] ?? null;
if (!$email) { echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit; }

$user = get_user_by_email($email);
if (!$user || $user['role'] !== 'Faculty') {
    echo json_encode(['ok'=>false,'error'=>'Only faculty can access assignments']); exit;
}

$file = __DIR__ . '/data/assignments.json';
if (!file_exists($file)) file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));

function read_assignments(string $f): array {
    $raw = @file_get_contents($f);
    $j = json_decode($raw ?: '[]', true);
    return is_array($j) ? $j : [];
}

function write_assignments(string $f, array $list): void {
    @file_put_contents($f, json_encode($list, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$assignments = read_assignments($file);

switch ($action) {
    case 'list':
        $facultyEmail = strtolower($email);
        $myAssignments = array_filter($assignments, fn($a) => strtolower($a['faculty_email'] ?? '') === $facultyEmail);
        echo json_encode(['ok'=>true,'items'=>array_values($myAssignments)]);
        break;
        
    case 'create':
        $title = trim($_POST['title'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $gradeLevel = trim($_POST['grade_level'] ?? '');
        $dueDate = trim($_POST['due_date'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (!$title || !$subject || !$gradeLevel || !$dueDate) {
            echo json_encode(['ok'=>false,'error'=>'Missing required fields']);
            break;
        }
        
        $newAssignment = [
            'id' => count($assignments) + 1,
            'faculty_email' => strtolower($email),
            'title' => $title,
            'subject' => $subject,
            'grade_level' => $gradeLevel,
            'due_date' => strtotime($dueDate),
            'description' => $description,
            'status' => time() > strtotime($dueDate) ? 'Completed' : 'Ongoing',
            'submissions' => 0,
            'total_students' => 30,
            'created' => time()
        ];
        
        $assignments[] = $newAssignment;
        write_assignments($file, $assignments);
        echo json_encode(['ok'=>true,'item'=>$newAssignment]);
        break;
        
    case 'update':
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['ok'=>false,'error'=>'Missing assignment ID']);
            break;
        }
        
        $facultyEmail = strtolower($email);
        foreach ($assignments as &$assignment) {
            if (intval($assignment['id']) === $id && strtolower($assignment['faculty_email'] ?? '') === $facultyEmail) {
                if (isset($_POST['title'])) $assignment['title'] = trim($_POST['title']);
                if (isset($_POST['description'])) $assignment['description'] = trim($_POST['description']);
                if (isset($_POST['due_date'])) {
                    $assignment['due_date'] = strtotime($_POST['due_date']);
                    $assignment['status'] = time() > $assignment['due_date'] ? 'Completed' : 'Ongoing';
                }
                
                write_assignments($file, $assignments);
                echo json_encode(['ok'=>true,'item'=>$assignment]);
                exit;
            }
        }
        echo json_encode(['ok'=>false,'error'=>'Assignment not found']);
        break;
        
    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            echo json_encode(['ok'=>false,'error'=>'Missing assignment ID']);
            break;
        }
        
        $facultyEmail = strtolower($email);
        $assignments = array_values(array_filter($assignments, fn($a) => 
            intval($a['id']) !== $id || strtolower($a['faculty_email'] ?? '') !== $facultyEmail
        ));
        
        write_assignments($file, $assignments);
        echo json_encode(['ok'=>true]);
        break;
        
    default:
        echo json_encode(['ok'=>false,'error'=>'Unknown action']);
}
