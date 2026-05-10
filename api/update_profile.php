<?php
require __DIR__ . '/../config.php';
$userId = require_auth();
verify_csrf();

if (!rate_limit('profile_update', 12, 60)) {
    json_response(['success' => false, 'message' => 'Too many profile updates. Please wait a minute.'], 429);
}

$payload = [
    'full_name' => clean_string($_POST['full_name'] ?? '', 120),
    'username' => clean_string($_POST['username'] ?? '', 60),
    'email' => filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ? clean_string($_POST['email'], 190) : '',
    'phone' => clean_string($_POST['phone'] ?? '', 40),
    'bio' => clean_string($_POST['bio'] ?? '', 500),
    'address' => clean_string($_POST['address'] ?? '', 255),
    'gender' => clean_string($_POST['gender'] ?? '', 40),
    'date_of_birth' => clean_string($_POST['date_of_birth'] ?? '', 10),
    'website' => filter_var($_POST['website'] ?? '', FILTER_VALIDATE_URL) ? clean_string($_POST['website'], 255) : null,
    'twitter' => filter_var($_POST['twitter'] ?? '', FILTER_VALIDATE_URL) ? clean_string($_POST['twitter'], 255) : null,
    'linkedin' => filter_var($_POST['linkedin'] ?? '', FILTER_VALIDATE_URL) ? clean_string($_POST['linkedin'], 255) : null,
    'github' => filter_var($_POST['github'] ?? '', FILTER_VALIDATE_URL) ? clean_string($_POST['github'], 255) : null,
];

if ($payload['full_name'] === '' || $payload['username'] === '' || $payload['email'] === '') {
    json_response(['success' => false, 'message' => 'Name, username, and valid email are required.'], 422);
}
if (!preg_match('/^[a-zA-Z0-9._-]{3,60}$/', $payload['username'])) {
    json_response(['success' => false, 'message' => 'Username may only contain letters, numbers, dots, dashes, and underscores.'], 422);
}
if ($payload['date_of_birth'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $payload['date_of_birth'])) {
    json_response(['success' => false, 'message' => 'Date of birth must use YYYY-MM-DD format.'], 422);
}

try {
    $pdo = db();
    $exists = $pdo->prepare('SELECT id FROM users WHERE (email = :email OR username = :username) AND id <> :id LIMIT 1');
    $exists->execute(['email' => $payload['email'], 'username' => $payload['username'], 'id' => $userId]);
    if ($exists->fetch()) {
        json_response(['success' => false, 'message' => 'That email or username is already taken.'], 409);
    }

    $stmt = $pdo->prepare('UPDATE users SET full_name = :full_name, username = :username, email = :email, phone = :phone, bio = :bio, address = :address, gender = :gender, date_of_birth = NULLIF(:date_of_birth, ""), website = :website, twitter = :twitter, linkedin = :linkedin, github = :github WHERE id = :id');
    $stmt->execute($payload + ['id' => $userId]);
} catch (Throwable $exception) {
    if (env_value('DEMO_AUTH', 'true') !== 'true') {
        json_response(['success' => false, 'message' => 'Database error while updating profile.'], 500);
    }
}

json_response(['success' => true, 'message' => 'Profile updated with premium polish.']);
