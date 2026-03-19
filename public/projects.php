<?php
$module = [
    'eyebrow' => 'Saved projects',
    'title' => 'Reusable workflow library',
    'description' => 'Store conversion presets, SEO campaigns, and edit blueprints for rapid reuse.',
    'features' => [
        ['icon' => '★', 'title' => 'Pinned presets', 'meta' => 'Keep your most-used conversion recipes ready.'],
        ['icon' => '⟳', 'title' => 'Repeatable automation', 'meta' => 'Launch the same workflow for teams and clients.'],
        ['icon' => '⇆', 'title' => 'Share internally', 'meta' => 'Team-ready structure for future collaboration.'],
        ['icon' => '▥', 'title' => 'History tracking', 'meta' => 'Map every output to a saved project.'],
    ],
    'content' => '<div class="space-y-4"><div class="value-card"><div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 text-lg">1</div><div><h3 class="font-semibold text-white">Brand Assets Conversion Pack</h3><p class="mt-1 text-sm text-slate-400">Resize logos, export WEBP variants, and share expiring download links.</p></div></div><div class="value-card"><div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 text-lg">2</div><div><h3 class="font-semibold text-white">SEO Article Launch</h3><p class="mt-1 text-sm text-slate-400">Generate metadata, internal links, and OG preview ideas.</p></div></div></div>',
];
require __DIR__ . '/_module_template.php';
