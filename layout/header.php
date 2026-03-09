<?php
declare(strict_types=1);
if (!isset($config)) { $config = require __DIR__ . '/../config/config.php'; }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($config['app']['title']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <header class="col-12 bg-dark text-white p-3"><h4 class="m-0"><?= h($config['app']['title']) ?></h4></header>
