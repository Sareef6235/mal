<?php
if (!function_exists('e')) { function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } }
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$adminName = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Administrator';
?>
<nav class="admin-topbar navbar navbar-expand-lg sticky-top">
  <div class="container-fluid gap-2">
    <button class="btn btn-light d-lg-none rounded-4" type="button" data-admin-sidebar-toggle aria-label="Toggle navigation"><i class="bi bi-list fs-4"></i></button>
    <div>
      <div class="small text-muted">Welcome back</div>
      <h1 class="h5 mb-0 fw-bold"><?= e($adminName) ?></h1>
    </div>
    <div class="ms-auto d-flex align-items-center gap-2">
      <button class="btn btn-light rounded-4" type="button" data-theme-toggle title="Toggle dark mode"><i class="bi bi-moon-stars"></i></button>
      <div class="dropdown">
        <button class="btn btn-light rounded-4 position-relative" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-bell"></i><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">3</span></button>
        <div class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-4 p-2 notification-menu">
          <div class="dropdown-header fw-bold">Notifications</div>
          <div class="dropdown-item rounded-3 small"><i class="bi bi-shield-check text-success me-2"></i>Admin panel secured</div>
          <div class="dropdown-item rounded-3 small"><i class="bi bi-people text-primary me-2"></i>User management ready</div>
          <div class="dropdown-item rounded-3 small"><i class="bi bi-database-check text-info me-2"></i>Using project config</div>
        </div>
      </div>
      <div class="dropdown">
        <button class="btn btn-primary rounded-4 d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false"><span class="avatar-sm"><?= e(strtoupper(substr($adminName,0,1))) ?></span><span class="d-none d-md-inline"><?= e($adminName) ?></span><i class="bi bi-chevron-down small"></i></button>
        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-4 p-2">
          <li><span class="dropdown-item-text small text-muted">Signed in as ADMIN</span></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item rounded-3" href="manage_ustads.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>
