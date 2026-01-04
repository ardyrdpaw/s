<?php
// layout_header.php: Header and sidebar for Support Apps BKPSDM
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}
// determine current script for active menu
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Apps BKPSDM</title>
    <link rel="icon" href="/favicon.ico">
    <link rel="icon" href="assets/images/logo.png" type="image/png">
    <link rel="shortcut icon" href="assets/images/logo.png" type="image/png">
    <link rel="apple-touch-icon" href="assets/images/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/custom.js"></script>
</head>
<body>
<header class="topbar bg-white shadow-sm py-2">
  <div class="container-fluid d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-light d-md-none" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">☰</button>
      <button id="sidebarCollapseBtn" class="btn btn-sm btn-light d-none d-md-inline" title="Toggle sidebar" aria-pressed="false">≡</button>
      <div class="brand d-flex align-items-center gap-2">
        <img src="assets/images/logo.png" alt="logo" style="height:34px; width:auto;">
        <strong class="brand-label">Support Apps BKPSDM</strong>
      </div>
    </div>
    <div class="topbar-actions d-flex align-items-center gap-2">
      <div class="dropdown">
        <?php $displayName = htmlspecialchars($_SESSION['name'] ?? ($_SESSION['role'] ?? 'User')); ?>
        <a class="btn btn-sm btn-light dropdown-toggle" href="#" role="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false"><?= $displayName ?></a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
          <li><a class="dropdown-item" href="profile.php">Profile</a></li>
          <li><a class="dropdown-item" href="preferences.php">Preferences</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
        </ul>
      </div>
    </div>
  </div>
</header>
<?php if (isset($_SESSION['user_id'])): ?>
<script>
(function(){
  function applyTheme(theme){
    if (theme === 'dark') { document.documentElement.classList.add('theme-dark'); document.body.classList.add('theme-dark'); }
    else { document.documentElement.classList.remove('theme-dark'); document.body.classList.remove('theme-dark'); }
  }
  // prefer local override when present
  var localTheme = localStorage.getItem('prefs_theme');
  if (localTheme) { applyTheme(localTheme); return; }
  // otherwise fetch saved preferences from server
  $.getJSON('php/user_api.php?action=preferences')
    .done(function(resp){
      if (resp && resp.data && resp.data.theme) {
        applyTheme(resp.data.theme);
        localStorage.setItem('prefs_theme', resp.data.theme);
      }
    }).fail(function(){ /* ignore failures silently */ });
})();
</script>
<?php endif; ?>
<div class="container-fluid">
  <div class="row">
    <nav id="sidebarMenu" class="col-md-2 col-lg-2 d-md-block bg-dark sidebar collapse">
      <div class="sidebar-inner p-3 text-white">
        <div class="mb-4">
          <h5 class="m-0">Main Menu</h5>
        </div>
        <ul class="nav flex-column">
          <li class="nav-item">
            <a class="nav-link text-white <?php echo $current_page === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php"><i class="bi bi-house"></i> <span class="menu-label ms-1">Home</span></a>
          </li>
          <?php
          // dynamic menus from DB (exclude admin-only 'user' and 'dashboard' to avoid duplicates)
          include_once __DIR__ . '/php/db_connect.php';
          $iconMap = [
            'cat' => 'bi-list-check',
            'signage' => 'bi-display',
            'inf_ti' => 'bi-hdd-network'
          ];
          $userId = intval(
            isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0
          );
          // join to user_access to respect per-user visibility when set (default visible)
          $res = $conn->query("SELECT m.menu_key as `key`, m.label, COALESCE(ua.visible, 1) as visible FROM menus m LEFT JOIN user_access ua ON ua.menu_key = m.menu_key AND ua.user_id = " . $userId . " ORDER BY m.sort_order ASC, m.label ASC");
          if ($res && $res->num_rows > 0) {
            while ($m = $res->fetch_assoc()) {
              $k = $m['key'];
              // skip dashboard (Home already present) and user (admin group)
              if (in_array($k, ['dashboard','user'])) continue;
              // if user is NOT admin and visible is explicitly 0 for current user, skip rendering
              if (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin' && isset($m['visible']) && intval($m['visible']) === 0) continue;
              $href = $k . '.php';
              $filePath = __DIR__ . '/' . $href;
              $link = file_exists($filePath) ? $href : '#';
              $active = ($current_page === basename($href)) ? 'active' : '';
              $icon = $iconMap[$k] ?? 'bi-circle';
              echo '<li class="nav-item">';
              echo '<a class="nav-link text-white ' . $active . '" href="' . $link . '"><i class="bi ' . $icon . '"></i> <span class="menu-label ms-1">' . htmlspecialchars($m['label']) . '</span></a>';
              echo '</li>';
            }
          } else {
            // fallback static items if menus table is empty
            ?>
            <li class="nav-item">
              <a class="nav-link text-white <?php echo $current_page === 'cat.php' ? 'active' : '' ?>" href="cat.php"><i class="bi bi-list-check"></i> <span class="menu-label ms-1">CAT</span></a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-white <?php echo $current_page === 'signage.php' ? 'active' : '' ?>" href="signage.php"><i class="bi bi-display"></i> <span class="menu-label ms-1">Signage</span></a>
            </li>
            <?php
          }
          ?>
          <?php if ($_SESSION['role'] === 'admin'): ?>
          <li class="nav-item mt-3">
            <div class="mb-4">
              <h5 class="m-0">Admin</h5>
            </div>
          </li> 
          <li class="nav-item">
            <a class="nav-link text-white <?php echo $current_page === 'user.php' ? 'active' : '' ?>" href="user.php"><i class="bi bi-people"></i> <span class="menu-label ms-1">Users</span></a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white <?php echo $current_page === 'user_access.php' ? 'active' : '' ?>" href="user_access.php"><i class="bi bi-shield-lock"></i> <span class="menu-label ms-1">Akses</span></a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-white <?php echo $current_page === 'menus.php' ? 'active' : '' ?>" href="menus.php"><i class="bi bi-list"></i> <span class="menu-label ms-1">Manage Menus</span></a>
          </li>
          <?php endif; ?>
          <li class="nav-item mt-3">
            <a class="nav-link text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> <span class="menu-label ms-1">Logout</span></a>
          </li>
        </ul>
      </div>
    </nav>
    <main class="col-md-10 ms-sm-auto col-lg-10 px-md-4">
