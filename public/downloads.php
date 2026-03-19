<?php
$module = [
    'eyebrow' => 'Downloads',
    'title' => 'Downloads and shared links',
    'description' => 'Track exported files, open previews, and share expiring links for delivery.',
    'features' => [
        ['icon' => '↓', 'title' => 'Download history', 'meta' => 'See the latest exported assets and files.'],
        ['icon' => '⌁', 'title' => 'Share links', 'meta' => 'Issue expiring public links for clients.'],
        ['icon' => '◩', 'title' => 'File preview', 'meta' => 'Display preview metadata before final download.'],
        ['icon' => '▦', 'title' => 'QR ready', 'meta' => 'Expose QR text for mobile scanning flows.'],
    ],
    'content' => '<div class="space-y-4"><div class="metric-card"><p class="font-semibold text-white">Recent export</p><p class="mt-2 text-sm text-slate-400">Use the converter page to generate files; share and download links are returned there automatically.</p></div></div>',
];
require __DIR__ . '/_module_template.php';
