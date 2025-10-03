<?php
require_once('helpers.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    // Validate inputs here (omitted for brevity)

    // Check if email already exists
    if (get_user_by_email($email)) {
        http_response_code(409);
        echo json_encode(['error'=>'Email already registered']);
        exit;
    }

    $secret = gen_totp_secret();

    $user = [
      'email' => $email,
      'password' => hash_password($password),
      'totp_secret' => $secret,
      // other user data...
    ];

    add_user($user);

    header('Content-Type: application/json');
    echo json_encode(['success'=>true, 'secret'=>$secret]);
    exit;
}
?>
