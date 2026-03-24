<?php

declare(strict_types=1);

const APP_NAME = 'Notification Manager';
const APP_URL = 'https://yourdomain.com';

const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'hvernued_conve';
const DB_USER = 'hvernued_cpses_hvnqmd5ph8';
const DB_PASS = 'Zirect@1618*1##';
const DB_CHARSET = 'utf8mb4';

const SESSION_NAME = 'notifmgr_session';
const MAX_UPLOAD_SIZE = 2 * 1024 * 1024; // 2MB
const UPLOAD_DIR = __DIR__ . '/../uploads/';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_name(SESSION_NAME);
    session_start();
}

date_default_timezone_set('UTC');
