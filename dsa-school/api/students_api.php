<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');

$email = $_SESSION['user_email'] ?? null;
if (!$email) { echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit; }
$actor = get_user_by_email($email);
if (!$actor || !in_array($actor['role'], ['Staff','Administrator'], true)) { echo json_encode(['ok'=>false,'error'=>'Forbidden']); exit; }

$file = __DIR__ . '/data/students.json';
if (!is_dir(__DIR__ . '/data')) @mkdir(__DIR__ . '/data', 0775, true);
if (!file_exists($file)) file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));

function read_students_file(string $f): array { $raw = @file_get_contents($f); $j = json_decode($raw ?: '[]', true); return is_array($j) ? $j : []; }
function write_students_file(string $f, array $list): void { @file_put_contents($f, json_encode($list, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)); }

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$students = read_students_file($file);

switch ($action) {
  case 'list':
    // Merge user accounts with role Student so they appear even if not yet in students.json
    $allUsers = read_users();
    $byEmail = [];
    foreach ($students as $s) { $byEmail[strtolower($s['email'] ?? '')] = true; }
    foreach ($allUsers as $u) {
      if (($u['role'] ?? '') === 'Student') {
        $e = strtolower($u['email'] ?? '');
        if (!isset($byEmail[$e])) {
          $students[] = [
            'id' => (int) (max(array_column($students, 'id')) ?: 0) + 1,
            'name' => $u['name'] ?? '',
            'email' => $u['email'] ?? '',
            'student_id' => $u['student_id'] ?? '',
            'grade_level' => $u['grade_level'] ?? '',
            'section' => $u['section'] ?? '',
            'enrollment_status' => 'Enrolled',
            'tuition_balance' => 0,
            'attendance_rate' => 0,
            'gpa' => 0,
            'created' => $u['created'] ?? time()
          ];
        }
      }
    }

    // Load enrollment assignments and update student sections
    $enrollmentFile = __DIR__ . '/data/enrollment.json';
    if (file_exists($enrollmentFile)) {
      $enrollmentRaw = @file_get_contents($enrollmentFile);
      $enrollmentData = json_decode($enrollmentRaw ?: '{}', true);
      $assignments = $enrollmentData['student_assignments'] ?? [];

      foreach ($students as &$student) {
        $studentId = $student['student_id'] ?? '';
        if (isset($assignments[$studentId])) {
          $assignment = $assignments[$studentId];
          $student['grade_level'] = $assignment['grade_level'];
          $student['section'] = $assignment['section'];
        }
      }
    }

    // Do not persist merged entries yet; list-only view
    echo json_encode(['ok'=>true,'items'=>$students]);
    break;
  case 'create':
    $name = trim($_POST['name'] ?? '');
    $studentId = trim($_POST['student_id'] ?? '');
    $studentEmail = trim($_POST['email'] ?? '');
    $grade = trim($_POST['grade_level'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $status = trim($_POST['status'] ?? 'Enrolled');
    if ($name === '' || $studentId === '' || $studentEmail === '' || $grade === '') { echo json_encode(['ok'=>false,'error'=>'Missing fields']); break; }
    // Validate grade level against K-10
    $validLevels = defined('SCHOOL_LEVELS') ? SCHOOL_LEVELS : ['Kinder','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8','Grade 9','Grade 10'];
    if (!in_array($grade, $validLevels, true)) { echo json_encode(['ok'=>false,'error'=>'Invalid grade level']); break; }
    $id = (int) (max(array_column($students, 'id')) ?: 0) + 1;
    $new = [
      'id' => $id,
      'name' => $name,
      'email' => $studentEmail,
      'student_id' => $studentId,
      'grade_level' => $grade,
      'section' => $section,
      'enrollment_status' => $status,
      'tuition_balance' => 0,
      'attendance_rate' => 0,
      'gpa' => 0,
      'created' => time()
    ];

    // Check if there's an enrollment assignment for this student
    $enrollmentFile = __DIR__ . '/data/enrollment.json';
    if (file_exists($enrollmentFile)) {
      $enrollmentRaw = @file_get_contents($enrollmentFile);
      $enrollmentData = json_decode($enrollmentRaw ?: '{}', true);
      $assignments = $enrollmentData['student_assignments'] ?? [];

      if (isset($assignments[$studentId])) {
        $assignment = $assignments[$studentId];
        $new['grade_level'] = $assignment['grade_level'];
        $new['section'] = $assignment['section'];
      }
    }

    $students[] = $new;
    write_students_file($file, $students);
    echo json_encode(['ok'=>true,'item'=>$new]);
    break;
  case 'update':
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['ok'=>false,'error'=>'Missing id']); break; }
    foreach ($students as $i => $s) {
      if ((int)$s['id'] === $id) {
        $fields = ['name','email','student_id','grade_level','section','enrollment_status','tuition_balance','attendance_rate','gpa'];
        foreach ($fields as $f) if (isset($_POST[$f])) $students[$i][$f] = $_POST[$f];
        write_students_file($file, $students);
        echo json_encode(['ok'=>true]);
        exit;
      }
    }
    echo json_encode(['ok'=>false,'error'=>'Not found']);
    break;
  case 'delete':
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['ok'=>false,'error'=>'Missing id']); break; }
    $students = array_values(array_filter($students, fn($s) => (int)$s['id'] !== $id));
    write_students_file($file, $students);
    echo json_encode(['ok'=>true]);
    break;
  default:
    echo json_encode(['ok'=>false,'error'=>'Unknown action']);
}

