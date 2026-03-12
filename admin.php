<?php
require __DIR__ . '/config/bootstrap.php';
require_role(['admin']);
header('Location: /admin/dashboard.php');
exit;
