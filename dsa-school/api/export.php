<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/helpers.php';

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

// Check if user has permission to export data
if (!has_permission(get_role_display_name($user['role']), 'Staff')) {
    http_response_code(403);
    echo json_encode(['error' => 'Insufficient permissions']);
    exit;
}

$type = $_GET['type'] ?? '';
$format = $_GET['format'] ?? 'csv';

if (!in_array($format, ['csv', 'json', 'xlsx'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid format']);
    exit;
}

// Sample data for export - in a real system, this would come from a database
$data = [];

switch ($type) {
    case 'students':
        $data = [
            ['Student ID', 'Name', 'Email', 'Grade Level', 'Section', 'Status', 'Tuition Balance', 'Attendance Rate', 'GPA'],
            ['2024-001', 'Juan Dela Cruz', 'juan.delacruz@slssr.edu.ph', 'Grade 10', 'St. Luke', 'Enrolled', '2500.00', '92.5', '3.2'],
            ['2024-002', 'Maria Santos', 'maria.santos@slssr.edu.ph', 'Grade 9', 'St. Mark', 'Enrolled', '0.00', '95.8', '3.8'],
            ['2024-003', 'Pedro Rodriguez', 'pedro.rodriguez@slssr.edu.ph', 'Grade 11', 'St. John', 'Dropped', '5000.00', '45.2', '2.1'],
        ];
        break;
        
    case 'users':
        $users = read_users();
        $data = [['Name', 'Email', 'Role', 'Created', '2FA Enabled']];
        foreach ($users as $u) {
            $data[] = [
                $u['name'],
                $u['email'],
                get_role_display_name($u['role']),
                date('Y-m-d H:i:s', $u['created']),
                !empty($u['totp_secret']) ? 'Yes' : 'No'
            ];
        }
        break;
        
    case 'grades':
        $data = [
            ['Student ID', 'Student Name', 'Subject', 'Q1', 'Q2', 'Q3', 'Q4', 'Final Grade', 'Status'],
            ['2024-001', 'Juan Dela Cruz', 'Mathematics', '85', '88', '90', '87', '87.5', 'PASSED'],
            ['2024-001', 'Juan Dela Cruz', 'English', '92', '89', '91', '88', '90.0', 'PASSED'],
            ['2024-002', 'Maria Santos', 'Mathematics', '95', '92', '94', '93', '93.5', 'PASSED'],
        ];
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data type']);
        exit;
}

// Set appropriate headers based on format
$filename = $type . '_export_' . date('Y-m-d_H-i-s');

switch ($format) {
    case 'csv':
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        break;
        
    case 'json':
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '.json"');
        
        $jsonData = [];
        $headers = array_shift($data);
        foreach ($data as $row) {
            $jsonData[] = array_combine($headers, $row);
        }
        
        echo json_encode($jsonData, JSON_PRETTY_PRINT);
        break;
        
    case 'xlsx':
        // For XLSX, we'll create a simple CSV with .xlsx extension
        // In a real application, you'd use a library like PhpSpreadsheet
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        
        $output = fopen('php://output', 'w');
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        break;
}
?>
