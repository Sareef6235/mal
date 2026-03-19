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

    $flat = [
        'score' => $payload['score'],
        'meta' => $payload['metaDescription'],
        'metaDescription' => $payload['metaDescription'],
        'density' => $payload['keywordDensity'],
        'keywordDensity' => $payload['keywordDensity'],
        'words' => str_word_count(strtolower((string) ($_POST['content'] ?? ''))),
        'suggestions' => implode(', ', array_filter([
            $payload['keywordDensity'] < 1 ? 'Increase keyword usage' : null,
            str_word_count((string) ($_POST['content'] ?? '')) < 100 ? 'Add more content' : null,
            empty($_POST['keyword']) ? 'Add a target keyword' : null,
        ])) ?: 'Maintain heading structure, improve internal links, and optimize media delivery.',
        'robots' => $payload['robots'],
        'sitemap' => $payload['sitemap'],
        'internalLinks' => $payload['internalLinks'],
        'blogOutline' => $payload['blogOutline'],
    ];

    echo json_encode($flat + ['data' => $flat]);
} catch (Throwable $exception) {
    http_response_code(422);
    echo json_encode(['error' => $exception->getMessage()]);
}
