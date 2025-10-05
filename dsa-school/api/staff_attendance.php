<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');

$email = $_SESSION['user_email'] ?? null;
if (!$email) { echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit; }
$actor = get_user_by_email($email);
if (!$actor || !in_array($actor['role'], ['Staff','Administrator'], true)) { echo json_encode(['ok'=>false,'error'=>'Forbidden']); exit; }

$file = __DIR__ . '/data/attendance.json';
if (!is_dir(__DIR__ . '/data')) @mkdir(__DIR__ . '/data', 0775, true);
if (!file_exists($file)) file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));

function read_att(string $f): array { $raw = @file_get_contents($f); $j = json_decode($raw ?: '[]', true); return is_array($j) ? $j : []; }
function write_att(string $f, array $list): void { @file_put_contents($f, json_encode($list, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)); }

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$records = read_att($file);

switch ($action) {
  case 'list':
    $date = $_GET['date'] ?? date('Y-m-d');
    $grade = $_GET['grade'] ?? '';
    $section = $_GET['section'] ?? '';
    $out = array_values(array_filter($records, function($r) use ($date,$grade,$section){
      if (($r['date'] ?? '') !== $date) return false;
      if ($grade && ($r['grade_level'] ?? '') !== $grade) return false;
      if ($section && ($r['section'] ?? '') !== $section) return false;
      return true;
    }));
    echo json_encode(['ok'=>true,'items'=>$out]);
    break;
  case 'save':
    $payload = $_POST['payload'] ?? '';
    $data = json_decode($payload, true);
    if (!is_array($data)) { echo json_encode(['ok'=>false,'error'=>'Invalid payload']); break; }
    // expected: { date, grade_level, section, students: [{student_id,status,remarks}] }
    $data['saved_by'] = $email;
    $records[] = $data;
    write_att($file, $records);
    echo json_encode(['ok'=>true]);
    break;
  case 'summary_today':
    $today = date('Y-m-d');
    $todayRecs = array_values(array_filter($records, fn($r)=>(($r['date'] ?? '') === $today)));
    $totalStudents = 0; $present = 0; $absent = 0; $late = 0;
    foreach ($todayRecs as $rec) {
      foreach (($rec['students'] ?? []) as $s) {
        $totalStudents++;
        switch (strtolower($s['status'] ?? '')) {
          case 'present': $present++; break;
          case 'late': $late++; break;
          case 'absent': $absent++; break;
        }
      }
    }
    $rate = $totalStudents ? round((($present+$late)/$totalStudents)*100, 1) : 0;
    echo json_encode(['ok'=>true,'summary'=>['total'=>$totalStudents,'present'=>$present,'late'=>$late,'absent'=>$absent,'rate'=>$rate]]);
    break;
  default:
    echo json_encode(['ok'=>false,'error'=>'Unknown action']);
}

