<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');

$email = $_SESSION['user_email'] ?? null;
if (!$email) { echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit; }
$actor = get_user_by_email($email);
if (!$actor || ($actor['role'] !== 'Staff' && $actor['role'] !== 'Administrator')) { echo json_encode(['ok'=>false,'error'=>'Forbidden']); exit; }

$dataFile = __DIR__ . '/data/enrollment.json';
if (!is_dir(__DIR__ . '/data')) @mkdir(__DIR__ . '/data', 0775, true);
if (!file_exists($dataFile)) file_put_contents($dataFile, json_encode(['yearLevels'=>[], 'sections'=>[], 'student_assignments'=>[]], JSON_PRETTY_PRINT));

function read_data(string $file): array { $raw = @file_get_contents($file); $j = json_decode($raw ?: '[]', true); return is_array($j) ? $j : ['yearLevels'=>[], 'sections'=>[]]; }
function write_data(string $file, array $data): void { @file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)); }

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$data = read_data($dataFile);

switch ($action) {
  case 'list':
    echo json_encode(['ok'=>true, 'data'=>$data]);
    break;
  case 'add_year_level':
    $level = trim($_POST['level'] ?? '');
    if ($level === '') { echo json_encode(['ok'=>false,'error'=>'Missing level']); break; }
    if (!in_array($level, $data['yearLevels'], true)) $data['yearLevels'][] = $level;
    write_data($dataFile, $data);
    echo json_encode(['ok'=>true]);
    break;
  case 'add_section':
    $level = trim($_POST['level'] ?? '');
    $section = trim($_POST['section'] ?? '');
    if ($level === '' || $section === '') { echo json_encode(['ok'=>false,'error'=>'Missing fields']); break; }
    $data['sections'][$level] = $data['sections'][$level] ?? [];
    if (!in_array($section, $data['sections'][$level], true)) $data['sections'][$level][] = $section;
    write_data($dataFile, $data);
    echo json_encode(['ok'=>true]);
    break;
  case 'assign_student':
    $studentId = trim($_POST['student_id'] ?? '');
    $level = trim($_POST['level'] ?? '');
    $section = trim($_POST['section'] ?? '');
    if ($studentId === '' || $level === '' || $section === '') { echo json_encode(['ok'=>false,'error'=>'Missing fields']); break; }

    // Load current enrollment data
    $enrollmentFile = __DIR__ . '/data/enrollment.json';
    $enrollmentData = read_data($enrollmentFile);

    // Initialize student assignments if not exists
    if (!isset($enrollmentData['student_assignments'])) {
      $enrollmentData['student_assignments'] = [];
    }

    // Update or create student assignment
    $enrollmentData['student_assignments'][$studentId] = [
      'student_id' => $studentId,
      'grade_level' => $level,
      'section' => $section,
      'assigned_by' => $email,
      'assigned_at' => time()
    ];

    write_data($enrollmentFile, $enrollmentData);
    echo json_encode(['ok'=>true]);
    break;
  default:
    echo json_encode(['ok'=>false,'error'=>'Unknown action']);
}
