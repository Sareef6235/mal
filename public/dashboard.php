<?php
require_once __DIR__ . '/../app/helpers/bootstrap.php';

use App\Helpers\Auth;

Auth::requireLogin();
$user = Auth::user();
$navItems = [
    ['label' => 'Overview', 'href' => 'dashboard.php'],
    ['label' => 'Conversions', 'href' => 'converter.php'],
    ['label' => 'Image editor', 'href' => 'image-editor.php'],
    ['label' => 'SEO tools', 'href' => 'seo-tools.php'],
    ['label' => 'Projects', 'href' => 'projects.php'],
    ['label' => 'Downloads', 'href' => 'downloads.php'],
    ['label' => 'Admin', 'href' => 'admin.php'],
    ['label' => 'Analytics', 'href' => 'analytics.php'],
];

$usageStats = [['Queued jobs', 14], ['Storage used', '2.4 GB'], ['SEO reports', 53], ['Share links', 19]];

$recentConversions = [
    ['name' => 'brand-assets.zip', 'result' => 'optimized WEBP set', 'status' => 'Completed'],
    ['name' => 'sales-deck.docx', 'result' => 'PDF export', 'status' => 'Ready'],
    ['name' => 'launch-video.mov', 'result' => 'MP4 + MP3 extract', 'status' => 'Processing'],
];

$seoProjects = [
    ['title' => 'Homepage metadata refresh', 'meta' => 'Score improved to 91/100'],
    ['title' => 'Black Friday sitemap batch', 'meta' => '124 pages included'],
    ['title' => 'Internal linking audit - blog', 'meta' => '42 new opportunities'],
];
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
                <?php foreach ($navItems as $item): ?>
                    <a href="<?= htmlspecialchars($item['href']) ?>" class="block rounded-2xl border border-white/10 bg-white/5 px-4 py-3 transition hover:border-cyan-300/30 hover:bg-cyan-400/10 hover:text-white"><?= htmlspecialchars($item['label']) ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="mt-6 rounded-3xl border border-white/10 bg-white/5 p-4">
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Plan</p>
                <p class="mt-2 text-xl font-semibold text-white"><?= htmlspecialchars(ucfirst($user['plan'] ?? 'starter')) ?></p>
                <p class="mt-2 text-sm text-slate-400">Upgrade to Pro for watermark-free exports, more jobs, and premium team workflows.</p>
            </div>
        </aside>
        <main class="space-y-6">
            <section class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <?php foreach ($usageStats as [$label, $value]): ?>
                    <div class="glass-card p-5">
                        <p class="text-sm text-slate-400"><?= htmlspecialchars($label) ?></p>
                        <p class="mt-3 text-4xl font-black"><?= htmlspecialchars((string) $value) ?></p>
                    </div>
                <?php endforeach; ?>
            </section>
            <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <div class="glass-card p-6">
                    <h2 class="text-2xl font-bold">Recent conversion history</h2>
                    <div class="mt-4 space-y-3 text-sm">
                        <?php foreach ($recentConversions as $entry): ?>
                            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="font-medium text-white"><?= htmlspecialchars($entry['name']) ?></p>
                                        <p class="mt-1 text-slate-400"><?= htmlspecialchars($entry['result']) ?></p>
                                    </div>
                                    <span class="rounded-full border border-white/10 bg-slate-950/60 px-3 py-1 text-xs text-slate-200"><?= htmlspecialchars($entry['status']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="glass-card p-6">
                    <h2 class="text-2xl font-bold">Saved SEO projects</h2>
                    <div class="mt-4 space-y-3 text-sm">
                        <?php foreach ($seoProjects as $entry): ?>
                            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                <p class="font-medium text-white"><?= htmlspecialchars($entry['title']) ?></p>
                                <p class="mt-1 text-slate-400"><?= htmlspecialchars($entry['meta']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <section class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
                <div class="glass-card p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-bold">Usage analytics</h2>
                        <a href="analytics.php" class="glass-button">Open analytics</a>
                    </div>
                    <div class="mt-6 flex h-48 items-end gap-3">
                        <div class="w-full rounded-t-2xl bg-cyan-400/70" style="height:58%"></div>
                        <div class="w-full rounded-t-2xl bg-sky-400/70" style="height:76%"></div>
                        <div class="w-full rounded-t-2xl bg-fuchsia-400/70" style="height:44%"></div>
                        <div class="w-full rounded-t-2xl bg-emerald-400/70" style="height:88%"></div>
                    </div>
                    <div class="mt-4 grid grid-cols-4 text-xs text-slate-400">
                        <span>Convert</span><span>SEO</span><span>Share</span><span>Pro</span>
                    </div>
                </div>
                <div class="glass-card p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-bold">Quick actions</h2>
                        <a href="index.php#workspace" class="glass-button">Home workspace</a>
                    </div>
                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <?php foreach ([
                            ['label' => 'New conversion', 'href' => 'converter.php'],
                            ['label' => 'Edit images', 'href' => 'image-editor.php'],
                            ['label' => 'Run SEO audit', 'href' => 'seo-tools.php'],
                            ['label' => 'Open admin panel', 'href' => 'admin.php'],
                        ] as $action): ?>
                            <a href="<?= htmlspecialchars($action['href']) ?>" class="rounded-2xl border border-white/10 bg-white/5 px-4 py-4 text-sm font-medium text-slate-200 transition hover:border-cyan-300/30 hover:bg-cyan-400/10 hover:text-white"><?= htmlspecialchars($action['label']) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
