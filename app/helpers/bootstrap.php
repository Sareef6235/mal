<?php
session_start();

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/../' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

if (is_file(__DIR__ . '/../config/.env.php')) {
    require_once __DIR__ . '/../config/.env.php';
} else {
    require_once __DIR__ . '/../config/env.example.php';
}
