<?php
require_once __DIR__ . '/helpers.php';
header('Content-Type: application/json');
$body = json_decode(file_get_contents('php://input'), true);
if(!$body) exit(json_encode(['success'=>false,'error'=>'Invalid input']));
$req = ['username'=>trim($body['username'] ?? ''),'email'=>trim($body['email'] ?? ''),'password'=>$body['password'] ?? '','role'=>$body['role'] ?? 'student'];
$allowed_domain = 'slssr.edu.ph';
if($req['username']==='' || $req['email']==='' || $req['password']==='') exit(json_encode(['success'=>false,'error'=>'Missing fields']));

// allow if email already exists in test data, otherwise require allowed domain
$email_domain = strtolower(substr(strrchr($req['email'], "@"), 1) ?: '');
$users = load_users();
$exists = false;
foreach($users as $u){ if(isset($u['email']) && $u['email'] === $req['email']){ $exists = true; break; } }
if(!$exists && $email_domain !== $allowed_domain){
	exit(json_encode(['success'=>false,'error'=>"Registration restricted to {$allowed_domain} domain"]));
}

$res = create_user($req['username'],$req['email'],$req['password'],$req['role']);
if(isset($res['error'])) exit(json_encode(['success'=>false,'error'=>$res['error']]));
// Return TOTP secret and otpauth URL so user can set up authenticator app
$secret = $res['totp_secret'];
$label = rawurlencode($req['email']);
$issuer = rawurlencode('DSA-School');
$otpauth = "otpauth://totp/{$issuer}:{$label}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";

// for demo, store pending setup note in session
$_SESSION['last_registered'] = $res['id'];

echo json_encode(['success'=>true,'secret'=>$secret,'otpauth'=>$otpauth]);
