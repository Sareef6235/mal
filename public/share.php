<?php
require_once __DIR__ . '/../app/helpers/bootstrap.php';

use App\Services\ConversionWorkspaceService;

$service = new ConversionWorkspaceService();
$token = $_GET['token'] ?? '';
$share = $service->getShare($token);
?><!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared Download - FluxStudio Suite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="bg-slate-950 text-white">
    <main class="mx-auto max-w-3xl px-4 py-10">
        <div class="glass-card p-6 lg:p-8">
            <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Share link</p>
            <?php if (!$share): ?>
                <h1 class="mt-2 text-3xl font-black">Link expired or invalid</h1>
                <p class="mt-3 text-slate-300">This shareable file link is no longer available.</p>
            <?php else: ?>
                <h1 class="mt-2 text-3xl font-black">Download shared file</h1>
                <p class="mt-3 text-slate-300">This link expires at <?= htmlspecialchars(date('Y-m-d H:i', (int) $share['expires_at'])) ?> UTC.</p>
                <div class="mt-6 rounded-3xl border border-white/10 bg-white/5 p-5">
                    <p class="text-lg font-semibold text-white"><?= htmlspecialchars($share['file']) ?></p>
                    <a class="primary-button mt-5" href="download.php?file=<?= rawurlencode($share['file']) ?>">Download file</a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
