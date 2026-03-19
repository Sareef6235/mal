<?php
$module = [
    'eyebrow' => 'Document tools',
    'title' => 'PDF and office workflows',
    'description' => 'Merge, split, compress, and convert documents in a cPanel-friendly workspace.',
    'features' => [
        ['icon' => '⊞', 'title' => 'PDF merge', 'meta' => 'Combine uploaded PDF sets into single exports.'],
        ['icon' => '⊟', 'title' => 'PDF split', 'meta' => 'Slice documents into targeted page ranges.'],
        ['icon' => '▣', 'title' => 'Image ↔ PDF', 'meta' => 'Bidirectional conversion flow for simple document handling.'],
        ['icon' => '⌘', 'title' => 'DOCX → PDF', 'meta' => 'Prepared for LibreOffice-based server conversion.'],
    ],
    'content' => '<div class="space-y-4"><div class="metric-card"><p class="text-sm text-slate-300">Document queue supports invoice packs, resumes, proposals, and SEO reports.</p></div><div class="metric-card"><p class="text-sm text-slate-300">Use this page to plug in Ghostscript, LibreOffice, or cloud document processors.</p></div></div>',
];
require __DIR__ . '/_module_template.php';
