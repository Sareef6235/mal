<?php
require_once __DIR__ . '/../../app/helpers/bootstrap.php';

use App\Helpers\Csrf;
use App\Services\SeoToolkitService;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    Csrf::verify($_POST['csrf_token'] ?? '');
    $service = new SeoToolkitService();
    $payload = $service->analyze([
        'url' => $_POST['url'] ?? '',
        'keyword' => $_POST['keyword'] ?? '',
        'content' => $_POST['content'] ?? '',
    ]);

    echo json_encode(['data' => $payload]);
} catch (Throwable $exception) {
    http_response_code(422);
    echo json_encode(['error' => $exception->getMessage()]);
}
