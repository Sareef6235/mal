<?php
require_once __DIR__ . '/../app/helpers/bootstrap.php';
/** @var array $module */
?><!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($module['title']) ?> - FluxStudio Suite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="bg-slate-950 text-white">
    <main class="mx-auto max-w-6xl px-4 py-8 lg:px-6">
        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <p class="text-sm uppercase tracking-[0.3em] text-slate-400"><?= htmlspecialchars($module['eyebrow']) ?></p>
                <h1 class="mt-2 text-4xl font-black"><?= htmlspecialchars($module['title']) ?></h1>
                <p class="mt-3 max-w-3xl text-slate-300"><?= htmlspecialchars($module['description']) ?></p>
            </div>
            <a href="index.php#workspace" class="glass-button">Back to workspace</a>
        </div>

        <section class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
            <aside class="glass-card p-6">
                <h2 class="text-xl font-semibold">Capabilities</h2>
                <div class="mt-5 space-y-3">
                    <?php foreach ($module['features'] as $feature): ?>
                        <div class="value-card">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 text-lg"><?= htmlspecialchars($feature['icon']) ?></div>
                            <div>
                                <h3 class="font-semibold text-white"><?= htmlspecialchars($feature['title']) ?></h3>
                                <p class="mt-1 text-sm text-slate-400"><?= htmlspecialchars($feature['meta']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </aside>
            <section class="glass-card p-6">
                <?= $module['content'] ?>
            </section>
        </section>
    </main>
    <script src="assets/js/app.js"></script>
</body>
</html>
