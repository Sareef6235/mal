<?php require_once 'config.php'; requireLogin(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Madras Fee Management System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">✨ Madras Fee System</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="add_fee.php">Add Fee</a></li>
        <li class="nav-item"><a class="nav-link" href="view_fees.php">View Fees</a></li>
        <li class="nav-item"><a class="nav-link" href="report.php">Reports</a></li>
        <?php if (isSuperAdmin()): ?><li class="nav-item"><a class="nav-link" href="manage_users.php">Users</a></li><?php endif; ?><li class="nav-item"><a class="nav-link text-warning" href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>
<div class="container">
