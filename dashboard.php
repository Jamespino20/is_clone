<?php
session_start();
require_once __DIR__ . '/api/helpers.php';
if(empty($_SESSION['user_id'])){
    header('Location: /dsa-school/');
    exit;
}
$user = find_user('id', $_SESSION['user_id']);
if(!$user){ header('Location: /dsa-school/'); exit; }
$role = $user['role'];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Dashboard — <?php echo htmlspecialchars($user['username']); ?></title>
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
  <main class="container">
    <h1>Dashboard</h1>
    <p>Welcome, <strong><?php echo htmlspecialchars($user['username']); ?></strong> (<?php echo htmlspecialchars($role); ?>)</p>

    <section class="card">
      <h2>Modules</h2>
      <ul>
        <?php if($role === 'admin'): ?>
          <li>Manage Users</li>
          <li>School Settings</li>
        <?php endif; ?>
        <?php if(in_array($role, ['teacher','admin'])): ?>
          <li>Gradebook</li>
          <li>Class Management</li>
        <?php endif; ?>
        <?php if(in_array($role, ['student','admin'])): ?>
          <li>My Classes</li>
          <li>Assignments</li>
        <?php endif; ?>
      </ul>
    </section>

    <section class="card">
      <h2>Account</h2>
      <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
      <p>2FA: <?php echo !empty($user['2fa_enabled']) ? 'Enabled' : 'Disabled'; ?></p>
      <p><a href="api/logout.php">Logout</a></p>
    </section>

  </main>
</body>
</html>
