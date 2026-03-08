<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

session_destroy();
header('Location: ' . app_url('login.php'));
exit;
