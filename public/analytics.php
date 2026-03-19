<?php
$module = [
    'eyebrow' => 'Analytics',
    'title' => 'Usage analytics dashboard',
    'description' => 'Track conversions, SEO reports, subscriptions, and storage optimization.',
    'features' => [
        ['icon' => '◫', 'title' => 'Conversion analytics', 'meta' => 'Monitor queue size and conversion volume.'],
        ['icon' => '⌗', 'title' => 'SEO reports', 'meta' => 'Measure page audits and content generation usage.'],
        ['icon' => '⇡', 'title' => 'Plan upgrades', 'meta' => 'Watch free-to-pro movement.'],
        ['icon' => '⚡', 'title' => 'Performance', 'meta' => 'Keep storage and processing fast.'],
    ],
    'content' => '<div class="space-y-4"><div class="rounded-3xl border border-white/10 bg-white/5 p-5"><div class="flex items-end gap-3 h-48"><div class="w-full rounded-t-2xl bg-cyan-400/70" style="height:62%"></div><div class="w-full rounded-t-2xl bg-sky-400/70" style="height:78%"></div><div class="w-full rounded-t-2xl bg-fuchsia-400/70" style="height:55%"></div><div class="w-full rounded-t-2xl bg-emerald-400/70" style="height:84%"></div></div><div class="mt-4 grid grid-cols-4 text-xs text-slate-400"><span>Uploads</span><span>SEO</span><span>Shares</span><span>Pro</span></div></div></div>',
];
require __DIR__ . '/_module_template.php';
