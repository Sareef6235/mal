<?php
require __DIR__ . '/config/bootstrap.php';
logout_user();
header('Location: /login.php');
exit;
