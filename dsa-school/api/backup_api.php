<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/data_structures.php';

header('Content-Type: application/json');

$email = $_SESSION['user_email'] ?? null;
if (!$email) { echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit; }

$user = get_user_by_email($email);
if (!$user || $user['role'] !== 'Administrator') {
    echo json_encode(['ok'=>false,'error'=>'Admin access required']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "backup_{$timestamp}.json";

        // Collect all data files
        $dataDir = __DIR__ . '/data';
        $backupData = [];

        // Get all JSON files in the data directory
        $dataFiles = [
            'activities.json',
            'notifications.json',
            'system_logs.json',
            'evaluation_responses.json',
            'document_requests.json',
            'enrollment.json',
            'students.json',
            'settings.json',
            'users.json'
        ];

        foreach ($dataFiles as $file) {
            $filePath = $dataDir . '/' . $file;
            if (file_exists($filePath)) {
                $content = @file_get_contents($filePath);
                if ($content !== false) {
                    $backupData[$file] = json_decode($content, true) ?: [];
                }
            }
        }

        // Create backup file
        $backupPath = $dataDir . '/' . $filename;
        $result = file_put_contents($backupPath, json_encode($backupData, JSON_PRETTY_PRINT));

        if ($result === false) {
            echo json_encode(['ok' => false, 'error' => 'Failed to create backup']);
            exit;
        }

        // Log the backup creation
        $ds = DataStructuresManager::getInstance();
        $ds->logActivity($email, 'backup_created', "Created backup: $filename");

        echo json_encode(['ok' => true, 'filename' => $filename, 'size' => $result]);
        break;

    case 'restore':
        $filename = trim($_POST['filename'] ?? '');
        if (empty($filename)) {
            echo json_encode(['ok' => false, 'error' => 'Filename required']);
            exit;
        }

        $backupPath = __DIR__ . '/data/' . $filename;
        if (!file_exists($backupPath)) {
            echo json_encode(['ok' => false, 'error' => 'Backup file not found']);
            exit;
        }

        // Load backup data
        $backupContent = @file_get_contents($backupPath);
        if ($backupContent === false) {
            echo json_encode(['ok' => false, 'error' => 'Failed to read backup file']);
            exit;
        }

        $backupData = json_decode($backupContent, true);
        if ($backupData === null) {
            echo json_encode(['ok' => false, 'error' => 'Invalid backup file format']);
            exit;
        }

        // Restore each file
        $dataDir = __DIR__ . '/data';
        foreach ($backupData as $file => $data) {
            $filePath = $dataDir . '/' . $file;
            $result = file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
            if ($result === false) {
                echo json_encode(['ok' => false, 'error' => "Failed to restore $file"]);
                exit;
            }
        }

        // Log the restore operation
        $ds = DataStructuresManager::getInstance();
        $ds->logActivity($email, 'backup_restored', "Restored from backup: $filename");

        echo json_encode(['ok' => true, 'message' => 'Backup restored successfully']);
        break;

    case 'delete':
        $filename = trim($_POST['filename'] ?? '');
        if (empty($filename)) {
            echo json_encode(['ok' => false, 'error' => 'Filename required']);
            exit;
        }

        $backupPath = __DIR__ . '/data/' . $filename;
        if (!file_exists($backupPath)) {
            echo json_encode(['ok' => false, 'error' => 'Backup file not found']);
            exit;
        }

        if (!unlink($backupPath)) {
            echo json_encode(['ok' => false, 'error' => 'Failed to delete backup file']);
            exit;
        }

        // Log the deletion
        $ds = DataStructuresManager::getInstance();
        $ds->logActivity($email, 'backup_deleted', "Deleted backup: $filename");

        echo json_encode(['ok' => true, 'message' => 'Backup deleted successfully']);
        break;

    case 'restore_upload':
        if (!isset($_FILES['backup_file'])) {
            echo json_encode(['ok' => false, 'error' => 'No file uploaded']);
            exit;
        }

        $file = $_FILES['backup_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['ok' => false, 'error' => 'File upload error']);
            exit;
        }

        if ($file['type'] !== 'application/json') {
            echo json_encode(['ok' => false, 'error' => 'Only JSON files are allowed']);
            exit;
        }

        // Read uploaded file
        $content = @file_get_contents($file['tmp_name']);
        if ($content === false) {
            echo json_encode(['ok' => false, 'error' => 'Failed to read uploaded file']);
            exit;
        }

        $backupData = json_decode($content, true);
        if ($backupData === null) {
            echo json_encode(['ok' => false, 'error' => 'Invalid backup file format']);
            exit;
        }

        // Restore each file
        $dataDir = __DIR__ . '/data';
        foreach ($backupData as $fileName => $data) {
            $filePath = $dataDir . '/' . $fileName;
            $result = file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
            if ($result === false) {
                echo json_encode(['ok' => false, 'error' => "Failed to restore $fileName"]);
                exit;
            }
        }

        // Log the restore operation
        $ds = DataStructuresManager::getInstance();
        $ds->logActivity($email, 'backup_restored_upload', "Restored from uploaded backup: " . $file['name']);

        echo json_encode(['ok' => true, 'message' => 'Backup restored successfully']);
        break;

    case 'list':
        $dataDir = __DIR__ . '/data';
        $backupFiles = glob($dataDir . '/backup_*.json');
        rsort($backupFiles);

        $backups = array_map(function($file) {
            return [
                'filename' => basename($file),
                'size' => filesize($file),
                'date' => filemtime($file)
            ];
        }, $backupFiles);

        echo json_encode(['ok' => true, 'backups' => $backups]);
        break;

    default:
        echo json_encode(['ok' => false, 'error' => 'Unknown action']);
}
