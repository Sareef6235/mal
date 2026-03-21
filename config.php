<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'Premium Support Desk',
        'base_url' => 'http://localhost:8000',
        'timezone' => 'Asia/Kolkata',
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'hvernued_conve',
        'username' => 'hvernued_cpses_hvnqmd5ph8',
        'password' => 'Zirect@1618*1##',
        'charset' => 'utf8mb4',
    ],
    'email' => [
        'enabled' => false,
        'from' => 'support@example.com',
        'admin_to' => '123v213@gmail.com',
        'transport' => 'mail',
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_username' => '123v213@gmail.com',
        'smtp_password' => 'mhn1234',
    ],
    'whatsapp' => [
        'enabled' => false,
        'phone' => '919999999999',
        'api_endpoint' => 'https://graph.facebook.com/v19.0/YOUR_PHONE_NUMBER_ID/messages',
        'access_token' => 'YOUR_WHATSAPP_TOKEN',
    ],
    'security' => [
        'session_name' => 'premium_support_session',
        'admin_seed_email' => '123v213@gmail.com',
        'admin_seed_password' => 'mhn1234',
    ],
];
