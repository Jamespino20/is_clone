<?php
declare(strict_types=1);

// Ensure we always return JSON, even on errors
header('Content-Type: application/json');

// Disable PHP error display to prevent HTML in JSON response
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Function to safely return JSON response
function json_response($data) {
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

// Wrap everything in try-catch to ensure JSON response
try {
    session_start();
    require_once __DIR__ . '/helpers.php';
    require_once __DIR__ . '/data_structures.php';

    $email = $_SESSION['user_email'] ?? null;
    if (!$email) {
        json_response(['ok' => false, 'error' => 'Unauthorized']);
    }

    $ds = DataStructuresManager::getInstance();
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch ($action) {
        case 'send_notification':
            try {
                $target = trim($_POST['target_email'] ?? '');
                $title = trim($_POST['title'] ?? '');
                $message = trim($_POST['message'] ?? '');
                $type = trim($_POST['type'] ?? 'info');

                if ($target === '' || $title === '' || $message === '') {
                    json_response(['ok' => false, 'error' => 'Missing fields']);
                }

                $ds->addNotification($target, $title, $message, $type);
                $ds->logActivity($email, 'send_notification', "to=$target title=$title");
                json_response(['ok' => true]);
            } catch (Exception $e) {
                json_response(['ok' => false, 'error' => 'Error sending notification: ' . $e->getMessage()]);
            }
            break;

        case 'mark_read':
            try {
                $idx = (int)($_POST['index'] ?? -1);
                if ($idx < 0) {
                    json_response(['ok' => false, 'error' => 'Invalid index']);
                }

                $ok = $ds->markNotificationRead($idx, $email);
                json_response(['ok' => $ok]);
            } catch (Exception $e) {
                json_response(['ok' => false, 'error' => 'Error marking notification as read: ' . $e->getMessage()]);
            }
            break;

        case 'list':
            try {
                $all = $ds->getNotificationQueue()->getAll();
                json_response(['ok' => true, 'items' => $all]);
            } catch (Exception $e) {
                json_response(['ok' => false, 'error' => 'Error loading notifications: ' . $e->getMessage()]);
            }
            break;

        default:
            json_response(['ok' => false, 'error' => 'Unknown action']);
    }

} catch (Exception $e) {
    // Ensure we always return JSON even if a fatal error occurs
    json_response(['ok' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>
