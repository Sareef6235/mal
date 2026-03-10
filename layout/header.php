<?php
declare(strict_types=1);
if (!isset($config)) { require_once __DIR__ . '/../bootstrap.php'; }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($config['app']['site_title'] ?? 'Madrasa ERP System') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="/assets/css/admin.css" rel="stylesheet">
</head>
<body>
<nav class="topbar navbar navbar-expand-lg">
  <div class="container-fluid">
    <span class="navbar-brand fw-semibold ms-2 ms-lg-0">Madrasa ERP System</span>
    <?php $helpTopic = basename((string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH)); ?>
    <div class="ms-auto d-flex align-items-center gap-2">
      <a class="btn btn-outline-info btn-sm" href="/pages/user_guide.php?topic=<?= e($helpTopic) ?>" data-bs-toggle="tooltip" data-bs-title="Open bilingual help"><i class="bi bi-question-circle me-1"></i>Help</a>
      <div class="dropdown">
        <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-life-preserver me-1"></i>Help Menu</button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="/pages/user_guide.php"><i class="bi bi-book me-2"></i>User Guide</a></li>
          <li><a class="dropdown-item" href="/pages/user_guide.php#dashboard"><i class="bi bi-speedometer2 me-2"></i>Dashboard Help</a></li>
          <li><a class="dropdown-item" href="/pages/user_guide.php#festivals"><i class="bi bi-calendar-event me-2"></i>Festival Help</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>
<div class="container-fluid">
  <div class="row">
