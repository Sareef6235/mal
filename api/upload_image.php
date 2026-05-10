<?php
require __DIR__ . '/../config.php';
$userId = require_auth();
verify_csrf();

if (!rate_limit('image_upload', 8, 60)) {
    json_response(['success' => false, 'message' => 'Upload rate limit reached. Please wait.'], 429);
}

$type = ($_POST['type'] ?? 'avatar') === 'cover' ? 'cover' : 'avatar';
$field = $type === 'cover' ? 'cover_photo' : 'avatar';
$folder = $type === 'cover' ? 'covers' : 'avatars';

if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    json_response(['success' => false, 'message' => 'Choose a valid image to upload.'], 422);
}
if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
    json_response(['success' => false, 'message' => 'Image must be 5MB or smaller.'], 422);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($_FILES['image']['tmp_name']);
$extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
if (!isset($extensions[$mime])) {
    json_response(['success' => false, 'message' => 'Only JPG, PNG, and WebP images are allowed.'], 422);
}

$targetDir = UPLOAD_DIR . '/' . $folder;
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}
$filename = $field . '_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
$target = $targetDir . '/' . $filename;
if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
    json_response(['success' => false, 'message' => 'Could not store uploaded image.'], 500);
}

try {
    $stmt = db()->prepare("UPDATE users SET {$field} = :filename WHERE id = :id");
    $stmt->execute(['filename' => $filename, 'id' => $userId]);
} catch (Throwable $exception) {
    if (env_value('DEMO_AUTH', 'true') !== 'true') {
        json_response(['success' => false, 'message' => 'Image saved, but database update failed.'], 500);
    }
}

json_response(['success' => true, 'message' => ucfirst($type) . ' uploaded successfully.', 'url' => UPLOAD_URL . '/' . $folder . '/' . $filename]);
