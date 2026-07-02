<?php
if (!function_exists('e')) { function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } }
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
function navActive($pages){ global $currentPage; return in_array($currentPage,(array)$pages,true)?'active':''; }
?>
<aside class="admin-sidebar" id="adminSidebar">
  <div class="sidebar-brand"><div class="brand-icon"><i class="bi bi-mortarboard-fill"></i></div><div><strong>AdminSuite</strong><span>User Management</span></div></div>
  <nav class="sidebar-nav">
    <a class="sidebar-link <?= navActive('manage_ustads.php') ?>" href="manage_ustads.php"><i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span></a>
    <a class="sidebar-link <?= navActive('add_ustad.php') ?>" href="add_ustad.php"><i class="bi bi-person-plus-fill"></i><span>Add User</span></a>
    <button class="sidebar-link sidebar-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#userMenu" aria-expanded="true"><i class="bi bi-people-fill"></i><span>Users</span><i class="bi bi-chevron-down ms-auto"></i></button>
    <div class="collapse show" id="userMenu">
      <a class="sidebar-sublink <?= navActive(['manage_ustads.php','view_ustad.php','edit_ustad.php']) ?>" href="manage_ustads.php">Manage Users</a>
      <a class="sidebar-sublink" href="export_ustads.php?type=print" target="_blank">Printable Report</a>
    </div>
  </nav>
  <div class="sidebar-footer"><i class="bi bi-shield-lock-fill"></i><div><strong>ADMIN only</strong><span>Secure access enabled</span></div></div>
</aside>
<div class="sidebar-backdrop" data-admin-sidebar-toggle></div>
