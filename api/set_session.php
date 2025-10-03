<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
  $_SESSION['email'] = $_POST['email'];
  echo json_encode(['success' => true]);
  exit;
}
http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
