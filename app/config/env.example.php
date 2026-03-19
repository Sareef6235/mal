<?php
$GLOBALS['app_config'] = [
    'app' => [
        'name' => 'FluxStudio Suite',
        'base_url' => 'https://your-domain.com',
        'upload_max_mb' => 100,
        'auto_delete_minutes' => 30,
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'fluxstudio',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    'integrations' => [
        'cloudinary_url' => '',
        'remove_bg_key' => '',
        'google_search_console_json' => '',
        'openai_api_key' => '',
    ],
];
