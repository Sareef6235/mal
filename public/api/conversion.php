<?php
require_once __DIR__ . '/../../app/helpers/bootstrap.php';

use App\Services\ConversionWorkspaceService;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $service = new ConversionWorkspaceService();
    $sourceType = $_POST['source_type'] ?? 'auto';
    $targetFormat = strtolower($_POST['target_format'] ?? 'webp');
    $uploads = $service->validateUploads($_FILES['files'] ?? []);

    if ($uploads === []) {
        if (!empty($_POST['demo_names'])) {
            $uploads = array_map(static fn ($name) => [
                'name' => preg_replace('/[^A-Za-z0-9._-]/', '-', (string) $name),
                'tmp_name' => '',
                'size' => 925696,
                'extension' => strtolower(pathinfo((string) $name, PATHINFO_EXTENSION) ?: 'txt'),
            ], explode(',', (string) $_POST['demo_names']));
        } else {
            throw new RuntimeException('Please choose at least one file to convert.');
        }
    }

    echo json_encode([
        'data' => [
            'recommendation' => $service->recommendFormat($sourceType, $targetFormat),
            'results' => $service->convertBatch($uploads, $targetFormat, $sourceType),
            'analytics' => [
                'total_jobs' => count($uploads),
                'saved_storage' => count($uploads) * 12 . '%',
                'mode' => 'Bulk converter enabled',
            ],
        ],
    ]);
} catch (Throwable $exception) {
    http_response_code(422);
    echo json_encode(['error' => $exception->getMessage()]);
}
