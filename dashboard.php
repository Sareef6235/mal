<?php
require_once 'config.php';
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}
$name = htmlspecialchars($_SESSION['name'] ?? 'User');
$role = htmlspecialchars($_SESSION['role'] ?? 'Member');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Premium Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/app.css">
</head>
<body class="dash-page">
<div class="app-shell">
  <aside class="sidebar" id="sidebar">
    <div class="brand">⚡ EduAdmin</div>
    <nav>
      <a class="menu-item active" href="#">📊 <span>Overview</span></a>
      <a class="menu-item" href="#">🎓 <span>Students</span></a>
      <a class="menu-item" href="#">🏫 <span>Classes</span></a>
      <a class="menu-item" href="#">💳 <span>Payments</span></a>
      <a class="menu-item" href="#">⚙️ <span>Settings</span></a>
    </nav>
    <button class="icon-btn ripple" id="collapseBtn">⇤</button>
  </aside>

  <div class="main-area">
    <header class="topbar">
      <button class="icon-btn ripple mobile-only" id="drawerBtn">☰</button>
      <div class="session-pill">🟢 Session Active</div>
      <div class="profile"><?= $name ?> <small><?= $role ?></small></div>
    </header>

    <main class="content fade-in-up">
      <section class="cards-grid">
        <article class="stat-card c1"><h4>Total Students</h4><p class="counter" data-target="2240">0</p></article>
        <article class="stat-card c2"><h4>Total Classes</h4><p class="counter" data-target="84">0</p></article>
        <article class="stat-card c3"><h4>Monthly Fee Status</h4><p class="counter" data-target="92">0</p><small>% collected</small></article>
        <article class="stat-card c4"><h4>Pending Payments</h4><p class="counter" data-target="142">0</p></article>
        <article class="stat-card c5"><h4>Active Users</h4><p class="counter" data-target="328">0</p></article>
        <article class="stat-card c6"><h4>System Health</h4><p class="counter" data-target="99">0</p><small>% uptime</small></article>
      </section>
    </main>
  </div>
</div>

<nav class="mobile-bottom-nav">
  <a href="#" class="active">🏠</a><a href="#">🎓</a><a href="#">💳</a><a href="#">👤</a>
</nav>
<script src="assets/app.js" defer></script>
</body>
</html>
