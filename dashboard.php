<?php
session_start();
require_once __DIR__ . '/api/helpers.php';
if(empty($_SESSION['user_email'])){ header('Location: index.php'); exit; }
$user = find_user_by_email($_SESSION['user_email']);
if(!$user){ header('Location: index.php'); exit; }
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Dashboard - <?=htmlspecialchars($user['name'])?></title>
	<link rel="stylesheet" href="assets/css/style.css">
  <script src="assets/js/app.js"></script>
</head>
<body>
	<main class="container">
		<div class="card">
			<h1>Welcome, <?=htmlspecialchars($user['name'])?></h1>
			<p>Role: <strong><?=htmlspecialchars($user['role'])?></strong></p>
			<p>Email: <?=htmlspecialchars($user['email'])?></p>
			<div style="margin-top:12px">
				<button id="logoutBtn">Logout</button>
			</div>
		</div>

		<div class="card">
			<h2>Modules</h2>
			<ul>
				<?php if($user['role']==='admin'): ?>
					<li>Admin Console</li>
					<li>User Management</li>
				<?php endif; ?>
				<?php if($user['role']==='teacher'): ?>
					<li>Classroom</li>
					<li>Grades</li>
				<?php endif; ?>
				<?php if($user['role']==='student'): ?>
					<li>My Courses</li>
					<li>Assignments</li>
				<?php endif; ?>
			</ul>
		</div>
	</main>
</body>
</html>

