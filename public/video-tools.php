<?php
$module = [
    'eyebrow' => 'Video tools',
    'title' => 'FFmpeg-ready video suite',
    'description' => 'Prepare video compression, format conversion, audio extraction, and thumbnail generation.',
    'features' => [
        ['icon' => '▶', 'title' => 'Format conversion', 'meta' => 'MP4, MOV, AVI, and MKV workflow support.'],
        ['icon' => '♫', 'title' => 'Audio extraction', 'meta' => 'Pull MP3/WAV tracks from uploaded video.'],
        ['icon' => '▤', 'title' => 'Thumbnail generation', 'meta' => 'Create previews for downloads and sharing.'],
        ['icon' => '⎘', 'title' => 'Compression profiles', 'meta' => 'Fast web presets for lower storage costs.'],
    ],
    'content' => '<div class="grid gap-4 md:grid-cols-2"><div class="metric-card"><p class="text-sm text-slate-300">Use FFmpeg on the server for production processing.</p></div><div class="metric-card"><p class="text-sm text-slate-300">This page is the correct destination for the workspace Video tools button.</p></div></div>',
];
require __DIR__ . '/_module_template.php';
