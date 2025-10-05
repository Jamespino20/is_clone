<?php
declare(strict_types=1);

// Expects the following variables to be set by the including page:
// - $user (array)
// - $userRole (string display form)
// - $unreadNotifications (array)
// - $subtitle (string) optional
// - $assetPrefix (string) optional, e.g. "" from root pages, ".." from subdirs

$subtitle = $subtitle ?? 'Information System';
$assetPrefix = isset($assetPrefix) ? rtrim($assetPrefix, '/') : '';
$asset = $assetPrefix ? $assetPrefix . '/assets' : 'assets';
$rootHref = $assetPrefix ? $assetPrefix . '/' : '';
?>
<header class="topbar">
	<div class="topbar-left">
		<img src="<?= htmlspecialchars($asset) ?>/img/school-logo.png" alt="School Logo" class="topbar-logo">
		<div class="topbar-title">
			<h1>St. Luke's School of San Rafael</h1>
			<span class="topbar-subtitle"><?= htmlspecialchars($subtitle) ?></span>
		</div>
	</div>
	<div class="topbar-right">
		<div class="user-info">
			<span class="user-name">Welcome, <?= htmlspecialchars($user['name'] ?? '') ?></span>
			<span class="user-role"><?= htmlspecialchars($userRole ?? '') ?></span>
		</div>
		<nav>
			<a href="<?= $rootHref ?>dashboard.php" class="nav-link">Dashboard</a>
			<a href="<?= $rootHref ?>profile.php" class="nav-link">Profile</a>
			<a href="<?= $rootHref ?>security.php" class="nav-link">Security</a>
			<a href="<?= $rootHref ?>notifications.php" class="nav-link">
				🔔 Notifications
				<?php if (!empty($unreadNotifications)): ?>
					<span class="badge bg-warning"><?= count($unreadNotifications) ?></span>
				<?php endif; ?>
			</a>
			<?php if (($userRole ?? '') === 'Administrator'): ?>
				<a href="<?= $rootHref ?>audit_logs.php" class="nav-link">📋 Audit Logs</a>
			<?php endif; ?>
			<a href="<?= $rootHref ?>api/logout.php" class="nav-link logout" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
		</nav>
	</div>
</header>
<script>
  (function(){
    // Global leave-warning (suppressed for internal nav and logout links)
    let suppress = false;
    document.addEventListener('click', function(e){
      const a = e.target.closest('a');
      if (!a) return;
      const href = a.getAttribute('href') || '';
      if (href.includes('api/logout.php')) return; // logout has its own confirm
      if (href && !href.startsWith('#')) suppress = true;
    }, true);
    window.addEventListener('beforeunload', function(e){
      if (suppress) return; // navigating via internal link
      e.preventDefault();
      e.returnValue = '';
    });

    // Optional: auto-refresh notifications badge every 30s
    try {
      const navNotif = document.querySelector('a.nav-link[href$="notifications.php"] .badge');
      const headerEl = document.querySelector('header.topbar');
      const userEmail = <?= json_encode($user['email'] ?? '') ?>;
      async function refreshNotif(){
        if (!navNotif || !userEmail) return;
        const res = await fetch('<?= $assetPrefix ? $assetPrefix . '/' : '' ?>api/notifications_api.php?action=list', { credentials:'same-origin' });
        const data = await res.json();
        if (!data || data.ok === false) return;
        const unread = (data.items||[]).filter(n => (!n.read) && (n.user_email === userEmail || (n.is_system && (!n.target_roles || n.target_roles.includes(<?= json_encode($user['role'] ?? '') ?>))))).length;
        if (unread > 0) {
          navNotif.textContent = unread;
          navNotif.style.display = '';
        } else {
          navNotif.textContent = '';
          navNotif.style.display = 'none';
        }
      }
      setInterval(refreshNotif, 30000);
      document.addEventListener('DOMContentLoaded', refreshNotif);
    } catch(_){}
  })();
</script>

