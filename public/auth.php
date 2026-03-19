<?php
require_once __DIR__ . '/../app/helpers/bootstrap.php';

use App\Helpers\Auth;
use App\Helpers\Csrf;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

Csrf::verify($_POST['csrf_token'] ?? '');
$action = $_POST['action'] ?? 'register';

try {
    if ($action === 'register') {
        Auth::register($_POST['name'] ?? '', $_POST['email'] ?? '', $_POST['password'] ?? '');
        $_SESSION['flash'] = 'Account created successfully. You can now access the dashboard.';
    } elseif ($action === 'login') {
        Auth::attempt($_POST['email'] ?? '', $_POST['password'] ?? '');
        $_SESSION['flash'] = 'Logged in successfully.';
    }
} catch (Throwable $exception) {
    $_SESSION['flash'] = $exception->getMessage();
}

header('Location: index.php#auth');
exit;
