<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';
header('Location: ' . app_url('login.php'));
exit;
