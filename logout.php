<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/AuthController.php';

AuthController::logout();
header('Location: login.php');
exit;
