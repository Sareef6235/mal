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
    <button class="btn btn-outline-primary d-lg-none" id="sidebarToggle"><i class="bi bi-list"></i></button>
    <span class="navbar-brand fw-semibold ms-2">Madrasa ERP System</span>
  </div>
</nav>
<div class="container-fluid">
  <div class="row">
