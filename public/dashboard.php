<?php
require_once __DIR__ . '/../app/helpers/bootstrap.php';

use App\Helpers\Auth;

Auth::requireLogin();
$user = Auth::user();
?><!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FluxStudio Suite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="bg-slate-950 text-white">
    <div class="mx-auto grid min-h-screen max-w-7xl gap-6 px-4 py-8 lg:grid-cols-[280px_1fr] lg:px-6">
        <aside class="glass-card p-5">
            <a href="index.php" class="text-xl font-bold">FluxStudio Suite</a>
            <p class="mt-2 text-sm text-slate-400">Welcome back, <?= htmlspecialchars($user['name']) ?>.</p>
            <nav class="mt-6 space-y-3 text-sm text-slate-200">
                <?php foreach (['Overview', 'Conversions', 'Image editor', 'SEO tools', 'Projects', 'Downloads', 'Settings'] as $item): ?>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3"><?= htmlspecialchars($item) ?></div>
                <?php endforeach; ?>
            </nav>
        </aside>
        <main class="space-y-6">
            <section class="grid gap-6 md:grid-cols-3">
                <?php foreach ([['Queued jobs', 14], ['Storage used', '2.4 GB'], ['SEO reports', 53]] as [$label, $value]): ?>
                    <div class="glass-card p-5">
                        <p class="text-sm text-slate-400"><?= htmlspecialchars($label) ?></p>
                        <p class="mt-3 text-4xl font-black"><?= htmlspecialchars((string) $value) ?></p>
                    </div>
                <?php endforeach; ?>
            </section>
            <section class="grid gap-6 xl:grid-cols-2">
                <div class="glass-card p-6">
                    <h2 class="text-2xl font-bold">Recent conversion history</h2>
                    <div class="mt-4 space-y-3 text-sm">
                        <?php foreach ([
                            'brand-assets.zip → optimized WEBP set',
                            'sales-deck.docx → PDF',
                            'launch-video.mov → MP4 + MP3 extract',
                        ] as $entry): ?>
                            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3"><?= htmlspecialchars($entry) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="glass-card p-6">
                    <h2 class="text-2xl font-bold">Saved SEO projects</h2>
                    <div class="mt-4 space-y-3 text-sm">
                        <?php foreach ([
                            'Homepage metadata refresh',
                            'Black Friday sitemap batch',
                            'Internal linking audit - blog',
                        ] as $entry): ?>
                            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3"><?= htmlspecialchars($entry) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
