<?php
$module = [
    'eyebrow' => 'Image editor',
    'title' => 'Image lab controls',
    'description' => 'Responsive image editor page with pro-ready capabilities, watermark logic, and API-ready enhancement hooks.',
    'features' => [
        ['icon' => '◫', 'title' => 'Resize/compress', 'meta' => 'Preset-driven optimization for web delivery.'],
        ['icon' => '✂', 'title' => 'Crop & rotate', 'meta' => 'Basic framing workflows for creators and stores.'],
        ['icon' => '✎', 'title' => 'Watermark policy', 'meta' => 'Free users receive branding watermark by default.'],
        ['icon' => '⬈', 'title' => 'AI enhancer ready', 'meta' => 'Prepared for upscale/sharpen API providers.'],
    ],
    'content' => '<div class="grid gap-4 md:grid-cols-2"><div class="feature-tile"><div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-lg">☼</div><div><h3 class="font-semibold text-white">Tone controls</h3><p class="mt-1 text-sm text-slate-400">Brightness, contrast, saturation, blur, and filters.</p></div></div><div class="feature-tile"><div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-lg">⌁</div><div><h3 class="font-semibold text-white">Background remover</h3><p class="mt-1 text-sm text-slate-400">Connect remove.bg or your own inference API.</p></div></div><div class="md:col-span-2 rounded-3xl border border-white/10 bg-slate-900/60 p-5"><p class="text-sm text-slate-300">This page is ready to host crop/resize canvases, slider controls, and upload previews.</p></div></div>',
];
require __DIR__ . '/_module_template.php';
