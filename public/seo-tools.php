<?php
$module = [
    'eyebrow' => 'SEO toolkit',
    'title' => 'Organic growth cockpit',
    'description' => 'Dedicated page for SEO score analysis, meta generation, sitemap hints, and AI content planning.',
    'features' => [
        ['icon' => '⌁', 'title' => 'Meta generator', 'meta' => 'Build descriptions and headline angles.'],
        ['icon' => '☍', 'title' => 'OpenGraph preview', 'meta' => 'Prepare social-ready preview content.'],
        ['icon' => '⌗', 'title' => 'Keyword density', 'meta' => 'Track phrase coverage and internal links.'],
        ['icon' => '⚡', 'title' => 'CWV UI', 'meta' => 'Showcase performance-focused recommendations.'],
    ],
    'content' => '<form id="seo-form" class="space-y-4"><input type="url" name="url" class="input-surface" placeholder="https://example.com" value="https://example.com"><input type="text" name="keyword" class="input-surface" value="online converter"><textarea name="content" rows="5" class="input-surface">A premium online converter, image editor, and SEO suite for fast cPanel deployment.</textarea><button type="submit" class="primary-button w-full">Run SEO analysis</button></form><div id="seo-results" class="mt-4 rounded-3xl border border-white/10 bg-white/5 p-5 text-slate-300">SEO results will appear here.</div>',
];
require __DIR__ . '/_module_template.php';
