<?php
$module = [
    'eyebrow' => 'Admin panel',
    'title' => 'Upload, users, and plan controls',
    'description' => 'Basic SaaS admin page for managing users, uploads, subscriptions, and notifications.',
    'features' => [
        ['icon' => '⚙', 'title' => 'User controls', 'meta' => 'Upgrade plans, suspend uploads, and inspect activity.'],
        ['icon' => '⇪', 'title' => 'Upload moderation', 'meta' => 'Review file volume and temporary storage usage.'],
        ['icon' => '✉', 'title' => 'Email notifications', 'meta' => 'Prepared for transactional notices.'],
        ['icon' => '☰', 'title' => 'Multilingual mode', 'meta' => 'Ready to route copy via translation dictionaries.'],
    ],
    'content' => '<div class="grid gap-4 md:grid-cols-3"><div class="metric-card"><p class="text-sm text-slate-400">Users</p><p class="mt-2 text-3xl font-bold text-white">1,284</p></div><div class="metric-card"><p class="text-sm text-slate-400">Free vs Pro</p><p class="mt-2 text-3xl font-bold text-white">78% / 22%</p></div><div class="metric-card"><p class="text-sm text-slate-400">Pending emails</p><p class="mt-2 text-3xl font-bold text-white">34</p></div></div><div class="mt-6 rounded-3xl border border-white/10 bg-white/5 p-5"><p class="text-sm text-slate-300">This admin page is the starting point for uploads moderation, subscription control, analytics, and notification management.</p></div>',
];
require __DIR__ . '/_module_template.php';
