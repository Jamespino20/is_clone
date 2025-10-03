<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/api/helpers.php';

$email = $_SESSION['user_email'] ?? null;
if (!$email) {
    header('Location: index.php');
    exit;
}
$user = get_user_by_email($email);
if (!$user) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="style.css" rel="stylesheet">
</head>
<body>
  <header class="topbar">
    <h1>Welcome, <?= htmlspecialchars($user['name']) ?></h1>
    <nav>
      <a href="api/logout.php">Logout</a>
    </nav>
  </header>

  <main class="container">
    <section class="card">
      <h2>Profile</h2>
      <p>Role: <strong><?= htmlspecialchars($user['role']) ?></strong></p>
      <p>Email: <?= htmlspecialchars($user['email']) ?></p>
    </section>

    <section class="card">
      <h2>Modules</h2>
      <ul>
        <?php if ($user['role'] === 'admin'): ?>
          <li>Admin Console</li>
          <li>User Management</li>
        <?php endif; ?>
        <?php if ($user['role'] !== 'teacher'): ?>
          <li>Classroom</li>
          <li>Grades</li>
        <?php endif; ?>
        <li>My Courses</li>
        <li>Assignments</li>
      </ul>
    </section>
  </main>
</body>
</html>
