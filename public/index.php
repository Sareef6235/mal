<?php
require_once __DIR__ . '/../app/helpers/bootstrap.php';

use App\Helpers\Auth;
use App\Helpers\Csrf;

$page = $_GET['page'] ?? 'home';
$user = Auth::user();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$stats = [
    'tools' => 24,
    'activeUsers' => 1284,
    'filesConverted' => 48291,
    'seoReports' => 7903,
];

$toolCategories = [
    'converter' => [
        'title' => 'Universal Converter',
        'description' => 'Batch-ready image, document, video, and audio conversions with progress tracking.',
        'items' => ['Image converter', 'Document converter', 'Video transcoder', 'Audio converter'],
        'gradient' => 'from-cyan-500/80 via-sky-500/70 to-indigo-500/80',
    ],
    'image' => [
        'title' => 'Advanced Image Editor',
        'description' => 'Resize, crop, watermark, compress, enhance, and prepare thumbnails in one workflow.',
        'items' => ['Resize & crop', 'AI enhancer ready', 'Background removal', 'Watermark studio'],
        'gradient' => 'from-fuchsia-500/80 via-pink-500/70 to-rose-500/80',
    ],
    'seo' => [
        'title' => 'SEO Toolkit',
        'description' => 'Generate metadata, robots.txt, sitemaps, content ideas, and diagnostic scores.',
        'items' => ['Meta generator', 'Sitemap builder', 'Keyword density', 'CWV dashboard'],
        'gradient' => 'from-emerald-500/80 via-teal-500/70 to-lime-500/80',
    ],
    'account' => [
        'title' => 'Workspace Dashboard',
        'description' => 'Secure accounts, saved projects, history, and cPanel-friendly deployment structure.',
        'items' => ['Usage dashboard', 'Saved projects', 'Download history', 'Cron cleanup ready'],
        'gradient' => 'from-amber-400/80 via-orange-500/70 to-red-500/80',
    ],
];

$recentActivities = [
    ['title' => 'PNG → WEBP batch', 'meta' => '12 images compressed • 2 min ago'],
    ['title' => 'SEO site audit', 'meta' => 'Homepage scored 91/100 • 14 min ago'],
    ['title' => 'PDF split project', 'meta' => 'Invoices export complete • 31 min ago'],
    ['title' => 'Video thumbnail', 'meta' => 'MP4 storyboard generated • 1 hr ago'],
];

?><!DOCTYPE html>
<html lang="en" x-data="appState()" x-bind:class="darkMode ? 'dark' : ''">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FluxStudio Suite - File Converter, Image Editor & SEO SaaS</title>
    <meta name="description" content="Production-ready PHP SaaS starter for file conversion, image editing, and SEO workflows.">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0f172a">
    <script>
        tailwind = window.tailwind || {};
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    boxShadow: {
                        glow: '0 0 0 1px rgba(255,255,255,0.08), 0 20px 60px rgba(56,189,248,0.22)',
                    },
                    colors: {
                        midnight: '#020617'
                    },
                    animation: {
                        float: 'float 8s ease-in-out infinite',
                        pulseSlow: 'pulse 4s ease-in-out infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-8px)' },
                        }
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="bg-slate-950 text-white antialiased selection:bg-cyan-400/30 selection:text-cyan-50">
    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute left-1/2 top-0 h-[32rem] w-[32rem] -translate-x-1/2 rounded-full bg-cyan-500/20 blur-3xl"></div>
        <div class="absolute bottom-0 right-0 h-[28rem] w-[28rem] rounded-full bg-fuchsia-500/20 blur-3xl"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(30,41,59,0.55),rgba(2,6,23,0.96)_45%)]"></div>
    </div>

    <header class="sticky top-0 z-40 border-b border-white/10 bg-slate-950/75 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 lg:px-6">
            <a href="index.php" class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-400 to-fuchsia-500 shadow-glow">
                    <span class="text-lg font-black">F</span>
                </div>
                <div>
                    <p class="text-lg font-semibold tracking-wide">FluxStudio Suite</p>
                    <p class="text-xs text-slate-300">Converter • Editor • SEO Workspace</p>
                </div>
            </a>
            <nav class="hidden items-center gap-6 text-sm text-slate-300 lg:flex">
                <a class="transition hover:text-white" href="#modules">Modules</a>
                <a class="transition hover:text-white" href="#workspace">Workspace</a>
                <a class="transition hover:text-white" href="#pricing">Plans</a>
                <a class="transition hover:text-white" href="#faq">FAQ</a>
            </nav>
            <div class="flex items-center gap-3">
                <button @click="darkMode = !darkMode; persistTheme()" class="glass-button hidden sm:inline-flex">Theme</button>
                <?php if ($user): ?>
                    <a class="glass-button" href="dashboard.php">Dashboard</a>
                <?php else: ?>
                    <a class="glass-button" href="#auth">Login</a>
                    <a class="primary-button" href="#auth">Start free</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="mx-auto flex max-w-7xl flex-col gap-10 px-4 py-8 lg:px-6 lg:py-10">
        <?php if ($flash): ?>
            <div class="rounded-2xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                <?= htmlspecialchars($flash) ?>
            </div>
        <?php endif; ?>

        <section class="grid gap-6 lg:grid-cols-[1.25fr_0.75fr]">
            <div class="glass-card p-6 lg:p-8">
                <div class="inline-flex items-center gap-2 rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-xs uppercase tracking-[0.3em] text-cyan-200">Production-ready PHP SaaS starter</div>
                <h1 class="mt-5 max-w-4xl text-4xl font-black leading-tight text-white md:text-6xl">Launch a premium file converter, image editor, and SEO toolkit on <span class="bg-gradient-to-r from-cyan-300 via-sky-300 to-fuchsia-300 bg-clip-text text-transparent">cPanel-ready PHP hosting</span>.</h1>
                <p class="mt-5 max-w-3xl text-base leading-7 text-slate-300 md:text-lg">This starter ships with responsive SaaS UI, AJAX workflows, modular PHP services, MySQL schema, install guide, PWA assets, and API-ready adapters for Cloudinary, remove.bg, and Google Search Console integrations.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="#workspace" class="primary-button">Explore workspace</a>
                    <a href="#modules" class="glass-button">Browse modules</a>
                </div>
                <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <?php foreach ($stats as $label => $value): ?>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-3xl font-bold text-white"><?= htmlspecialchars((string) $value) ?></p>
                            <p class="mt-2 text-sm text-slate-400"><?= htmlspecialchars(ucwords(preg_replace('/([a-z])([A-Z])/', '$1 $2', $label))) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="glass-card relative overflow-hidden p-6">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-cyan-400 via-fuchsia-400 to-emerald-400"></div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Live workflow</p>
                        <h2 class="mt-2 text-2xl font-bold">Smart operations panel</h2>
                    </div>
                    <span class="rounded-full border border-emerald-400/40 bg-emerald-400/10 px-3 py-1 text-xs text-emerald-200">Realtime AJAX</span>
                </div>
                <div class="mt-6 space-y-4">
                    <?php foreach ($recentActivities as $item): ?>
                        <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-white"><?= htmlspecialchars($item['title']) ?></p>
                                    <p class="mt-1 text-sm text-slate-400"><?= htmlspecialchars($item['meta']) ?></p>
                                </div>
                                <div class="h-3 w-3 rounded-full bg-emerald-400 shadow-[0_0_25px_rgba(74,222,128,0.8)]"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-6 rounded-2xl border border-white/10 bg-white/5 p-4">
                    <p class="text-sm text-slate-300">Includes cPanel-friendly structure, cron cleanup script, secure uploads, CSRF protection, and sample queues for file conversion pipelines.</p>
                </div>
            </div>
        </section>

        <section id="workspace" class="grid gap-6 xl:grid-cols-[0.78fr_1.22fr]" x-data="workspaceHub()">
            <aside class="glass-card p-5 lg:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Dashboard</p>
                        <h2 class="mt-2 text-2xl font-bold">Unified workspace</h2>
                    </div>
                    <span class="rounded-full border border-cyan-400/20 bg-cyan-400/10 px-3 py-1 text-xs text-cyan-100">PWA ready</span>
                </div>

                <div class="mt-6 space-y-3 text-sm">
                    <?php foreach ([
                        ['id' => 'convert', 'label' => 'Quick convert', 'icon' => '⇄'],
                        ['id' => 'image', 'label' => 'Image lab', 'icon' => '✦'],
                        ['id' => 'docs', 'label' => 'Document center', 'icon' => '▣'],
                        ['id' => 'video', 'label' => 'Video tools', 'icon' => '▶'],
                        ['id' => 'seo', 'label' => 'SEO cockpit', 'icon' => '⌁'],
                        ['id' => 'projects', 'label' => 'Saved projects', 'icon' => '★'],
                        ['id' => 'downloads', 'label' => 'Download history', 'icon' => '↓'],
                    ] as $nav): ?>
                        <a
                            href="<?= htmlspecialchars(match ($nav['id']) { 'convert' => 'converter.php', 'image' => 'image-editor.php', 'docs' => 'document-tools.php', 'video' => 'video-tools.php', 'seo' => 'seo-tools.php', 'projects' => 'projects.php', 'downloads' => 'downloads.php', default => 'index.php#workspace', }) ?>"
                            @mouseenter="activePanel = '<?= htmlspecialchars($nav['id']) ?>'"
                            :class="activePanel === '<?= htmlspecialchars($nav['id']) ?>' ? 'border-cyan-300/40 bg-cyan-400/10 text-white shadow-glow-soft' : 'border-white/10 bg-white/5 text-slate-200'"
                            class="flex w-full items-center justify-between rounded-2xl border px-4 py-3 text-left transition duration-200 hover:border-cyan-300/40 hover:bg-cyan-400/10 hover:text-white"
                        >
                            <span class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-sm"><?= htmlspecialchars($nav['icon']) ?></span>
                                <span><?= htmlspecialchars($nav['label']) ?></span>
                            </span>
                            <span>→</span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="mt-6 rounded-3xl border border-white/10 bg-gradient-to-br from-white/10 to-white/5 p-4">
                    <div class="mb-4 rounded-2xl border border-white/10 bg-slate-950/50 p-4">
                        <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Live status</p>
                        <p class="mt-2 text-lg font-semibold text-white" x-text="panelMeta[activePanel].title"></p>
                        <p class="mt-2 text-sm leading-6 text-slate-300" x-text="panelMeta[activePanel].description"></p>
                    </div>
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Deployment</p>
                    <h3 class="mt-2 text-lg font-semibold">cPanel checklist</h3>
                    <ul class="mt-3 space-y-2 text-sm text-slate-300">
                        <li>• Upload files into <code>public_html</code></li>
                        <li>• Import SQL schema</li>
                        <li>• Update <code>.env.php</code> values</li>
                        <li>• Point cron to cleanup script</li>
                    </ul>
                </div>
            </aside>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="glass-card p-5 lg:col-span-2 lg:p-6">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Universal converter</p>
                            <h2 class="mt-2 text-2xl font-bold">Drag, drop, queue, and monitor conversions</h2>
                        </div>
                        <div class="rounded-2xl border border-cyan-400/30 bg-cyan-400/10 px-4 py-3 text-sm text-cyan-100">Supports images, PDFs, DOCX, videos, audio, batch jobs, and instant previews.</div>
                    </div>

                    <div x-data="converterWidget()" class="mt-6 grid gap-6 xl:grid-cols-[1fr_0.9fr]">
                        <div>
                            <label for="converter-files" class="upload-zone relative flex min-h-[16rem] cursor-pointer flex-col items-center justify-center overflow-hidden rounded-3xl border-2 border-dashed border-white/15 bg-slate-900/60 p-6 text-center">
                                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(34,211,238,0.12),transparent_55%)]"></div>
                                <input id="converter-files" type="file" class="hidden" multiple @change="loadFiles($event)">
                                <div class="animate-float rounded-full bg-white/10 p-4 text-3xl">⬆</div>
                                <h3 class="mt-4 text-xl font-semibold">Drop files anywhere here</h3>
                                <p class="mt-2 max-w-md text-sm text-slate-400">Batch upload with type validation, secure sanitization, lazy preview generation, and timed cleanup.</p>
                                <div class="mt-5 flex flex-wrap justify-center gap-2 text-xs text-slate-300">
                                    <span class="chip">JPG</span>
                                    <span class="chip">PNG</span>
                                    <span class="chip">WEBP</span>
                                    <span class="chip">PDF</span>
                                    <span class="chip">MP4</span>
                                    <span class="chip">MP3</span>
                                </div>
                            </label>
                            <div class="mt-4 grid gap-3 md:grid-cols-3">
                                <select x-model="sourceType" class="input-surface">
                                    <option value="auto">Auto detect</option>
                                    <option value="image">Image</option>
                                    <option value="document">Document</option>
                                    <option value="video">Video</option>
                                    <option value="audio">Audio</option>
                                </select>
                                <select x-model="targetFormat" class="input-surface">
                                    <option value="webp">WEBP</option>
                                    <option value="png">PNG</option>
                                    <option value="jpg">JPG</option>
                                    <option value="pdf">PDF</option>
                                    <option value="mp3">MP3</option>
                                    <option value="mp4">MP4</option>
                                </select>
                                <button @click="startMockConversion()" class="primary-button w-full">Start conversion</button>
                            </div>
                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                <div class="metric-card">
                                    <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Queue</p>
                                    <p class="mt-2 text-2xl font-bold text-white" x-text="files.length"></p>
                                </div>
                                <div class="metric-card">
                                    <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Preset</p>
                                    <p class="mt-2 text-2xl font-bold text-white" x-text="sourceType === 'auto' ? 'Smart' : sourceType"></p>
                                </div>
                                <div class="metric-card">
                                    <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Cleanup</p>
                                    <p class="mt-2 text-2xl font-bold text-white">30m</p>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-slate-300">Queue status</span>
                                    <span class="text-cyan-200" x-text="progress + '%'">0%</span>
                                </div>
                                <div class="mt-3 h-3 overflow-hidden rounded-full bg-slate-800">
                                    <div class="h-full rounded-full bg-gradient-to-r from-cyan-400 via-sky-400 to-fuchsia-400 transition-all duration-300" :style="`width:${progress}%`"></div>
                                </div>
                            </div>
                            <div class="rounded-3xl border border-white/10 bg-slate-900/60 p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-white">Smart preview</p>
                                        <p class="mt-1 text-xs text-slate-400">Shows a sample queued item before real uploads.</p>
                                    </div>
                                    <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-300" x-text="targetFormat.toUpperCase()">WEBP</span>
                                </div>
                                <div class="mt-4 flex items-center gap-4 rounded-2xl border border-white/10 bg-white/5 p-4">
                                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-400/20 to-fuchsia-400/20 text-2xl">🖼</div>
                                    <div>
                                        <p class="font-medium text-white" x-text="files[0]?.name ?? 'preview.jpg'"></p>
                                        <p class="text-sm text-slate-400" x-text="files[0] ? Math.round(files[0].size / 1024) + ' KB' : '904 KB'"></p>
                                    </div>
                                </div>
                            </div>
                            <template x-for="file in files" :key="file.name">
                                <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-4 text-sm transition hover:-translate-y-0.5 hover:border-cyan-300/30">
                                    <div class="flex items-center justify-between gap-4">
                                        <div>
                                            <p class="font-medium text-white" x-text="file.name"></p>
                                            <p class="text-slate-400" x-text="Math.round(file.size / 1024) + ' KB'"></p>
                                        </div>
                                        <span class="rounded-full bg-white/10 px-3 py-1 text-xs text-slate-300" x-text="targetFormat.toUpperCase()"></span>
                                    </div>
                                </div>
                            </template>
                            <div x-show="!files.length" class="rounded-2xl border border-dashed border-white/10 p-6 text-sm text-slate-400">Selected files will appear here with progress and previews.</div>
                            <div id="conversion-results" class="rounded-3xl border border-white/10 bg-white/5 p-5 text-sm text-slate-300">Converted files, download links, and share links will appear here after you start a conversion.</div>
                        </div>
                    </div>
                </section>

                <section class="glass-card p-5 lg:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Image editor</p>
                            <h2 class="mt-2 text-2xl font-bold">Creative controls</h2>
                        </div>
                        <span class="rounded-full bg-fuchsia-400/10 px-3 py-1 text-xs text-fuchsia-200">API ready</span>
                    </div>
                    <div class="mt-6 grid gap-4">
                        <?php foreach ([
                            ['icon' => '◫', 'title' => 'Resize & compress', 'meta' => 'Responsive exports for web, social, and marketplace images'],
                            ['icon' => '⤾', 'title' => 'Crop / rotate / flip', 'meta' => 'Precision framing with quick presets and freeform controls'],
                            ['icon' => '✂', 'title' => 'Background remover', 'meta' => 'remove.bg / API-ready pipeline for product photography'],
                            ['icon' => '⬈', 'title' => 'AI upscale', 'meta' => 'Sharpen low-res assets and prep thumbnails or banners'],
                            ['icon' => '✎', 'title' => 'Watermark', 'meta' => 'Text/image watermark positioning with brand-safe spacing'],
                            ['icon' => '◌', 'title' => 'Filters & blur', 'meta' => 'Brightness, contrast, saturation, blur, and visual styling'],
                            ['icon' => '▥', 'title' => 'Thumbnail generator', 'meta' => 'Auto-create packs for YouTube, blog, and product grids'],
                        ] as $feature): ?>
                            <div class="feature-tile">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-lg"><?= htmlspecialchars($feature['icon']) ?></div>
                                <div>
                                    <h3 class="font-semibold text-white"><?= htmlspecialchars($feature['title']) ?></h3>
                                    <p class="mt-1 text-sm leading-6 text-slate-400"><?= htmlspecialchars($feature['meta']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="glass-card p-5 lg:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm uppercase tracking-[0.3em] text-slate-400">SEO toolkit</p>
                            <h2 class="mt-2 text-2xl font-bold">AI-assisted organic growth</h2>
                        </div>
                        <span class="rounded-full bg-emerald-400/10 px-3 py-1 text-xs text-emerald-200">Traffic-focused</span>
                    </div>
                    <form id="seo-form" class="mt-6 space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                        <input type="url" name="url" class="input-surface" placeholder="https://example.com" value="https://example.com">
                        <input type="text" name="keyword" class="input-surface" placeholder="Target keyword" value="file converter">
                        <textarea name="content" rows="4" class="input-surface" placeholder="Paste page content or product summary">Fast online file conversion, image optimization, SEO audits, PDF tools, and media workflows for marketers, agencies, and creators.</textarea>
                        <button type="submit" class="primary-button w-full">Generate SEO insights</button>
                    </form>
                    <div id="seo-results" class="mt-4 rounded-2xl border border-white/10 bg-slate-900/60 p-4 text-sm text-slate-300">Results will render here via Fetch API.</div>
                </section>
            </div>
        </section>

        <section id="modules" class="grid gap-6 lg:grid-cols-2 xl:grid-cols-4">
            <?php foreach ([
                'converter' => 'converter.php',
                'image' => 'image-editor.php',
                'seo' => 'seo-tools.php',
                'account' => 'dashboard.php',
            ] as $key => $link): $tool = $toolCategories[$key]; ?>
                <a href="<?= htmlspecialchars($link) ?>" class="glass-card group block overflow-hidden p-5">
                    <div class="h-1 rounded-full bg-gradient-to-r <?= htmlspecialchars($tool['gradient']) ?>"></div>
                    <h3 class="mt-5 text-2xl font-bold text-white"><?= htmlspecialchars($tool['title']) ?></h3>
                    <p class="mt-3 text-sm leading-6 text-slate-300"><?= htmlspecialchars($tool['description']) ?></p>
                    <ul class="mt-5 space-y-2 text-sm text-slate-300">
                        <?php foreach ($tool['items'] as $item): ?>
                            <li>• <?= htmlspecialchars($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="mt-6 inline-flex items-center gap-2 text-sm font-medium text-cyan-200 transition group-hover:gap-3">Open module <span>→</span></div>
                </a>
            <?php endforeach; ?>
        </section>

        <section id="auth" class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <div class="glass-card p-6">
                <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Authentication</p>
                <h2 class="mt-2 text-3xl font-bold">Secure user system</h2>
                <p class="mt-3 text-slate-300">Login and registration are now split into separate cards so you can sign in immediately using the seeded demo account or create a new user.</p>
                <div class="mt-6 grid gap-6 lg:grid-cols-2">
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-semibold text-white">Login</h3>
                            <span class="rounded-full bg-cyan-400/10 px-3 py-1 text-xs text-cyan-200">Ready now</span>
                        </div>
                        <form class="mt-4 space-y-4" method="post" action="auth.php">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                            <input type="hidden" name="action" value="login">
                            <input name="email" type="email" class="input-surface" placeholder="Email address" value="123v213@gmail.com" required>
                            <input name="password" type="password" class="input-surface" placeholder="Password" value="mhn1234" required>
                            <button class="primary-button w-full">Login now</button>
                        </form>
                        <div class="mt-4 rounded-2xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-xs text-emerald-100">
                            Seeded demo login: <strong>123v213@gmail.com</strong> / <strong>mhn1234</strong>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-semibold text-white">Register</h3>
                            <span class="rounded-full bg-fuchsia-400/10 px-3 py-1 text-xs text-fuchsia-200">New users</span>
                        </div>
                        <form class="mt-4 space-y-4" method="post" action="auth.php">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                            <input type="hidden" name="action" value="register">
                            <input name="name" class="input-surface" placeholder="Full name" required>
                            <input name="email" type="email" class="input-surface" placeholder="Email address" required>
                            <input name="password" type="password" class="input-surface" placeholder="Create password" required>
                            <button class="glass-button w-full border border-white/10">Create account</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="glass-card p-6">
                <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Platform values</p>
                <h2 class="mt-2 text-3xl font-bold">Built for scale</h2>
                <p class="mt-3 text-slate-300">Every block is redesigned to look more premium and behave like a serious SaaS product on mobile, tablet, and desktop.</p>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <?php foreach ([
                        ['icon' => '⛨', 'title' => 'Secure uploads', 'meta' => 'Validation, sanitization, and safer file handling'],
                        ['icon' => '⌘', 'title' => 'Role-ready schema', 'meta' => 'Prepared for plans, teams, and future access layers'],
                        ['icon' => '⚙', 'title' => 'Reusable services', 'meta' => 'Modular PHP services with clean extension points'],
                        ['icon' => '⟳', 'title' => 'Auto cleanup cron', 'meta' => 'Timed cleanup flow for temporary uploads'],
                        ['icon' => '◍', 'title' => 'PWA assets', 'meta' => 'Manifest and service worker for app-like installability'],
                        ['icon' => '⇆', 'title' => 'API adapters', 'meta' => 'Cloudinary, AI, Google, and remove.bg ready'],
                        ['icon' => '☾', 'title' => 'Dark mode', 'meta' => 'Luxury glass UI with smooth hover states'],
                        ['icon' => '▣', 'title' => 'Responsive layout', 'meta' => 'High-end layout that scales beautifully across devices'],
                    ] as $value): ?>
                        <div class="value-card">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 text-lg"><?= htmlspecialchars($value['icon']) ?></div>
                            <div>
                                <h3 class="font-semibold text-white"><?= htmlspecialchars($value['title']) ?></h3>
                                <p class="mt-1 text-sm leading-6 text-slate-400"><?= htmlspecialchars($value['meta']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section id="pricing" class="grid gap-6 lg:grid-cols-3">
            <?php foreach ([
                ['name' => 'Starter', 'price' => '$0', 'tag' => 'For creators', 'cta' => 'Launch Starter', 'features' => ['3 daily conversions', 'Basic image tools', 'SEO previews', 'Single user workspace'], 'tone' => ''],
                ['name' => 'Pro', 'price' => '$19', 'tag' => 'Most popular', 'cta' => 'Go Pro', 'features' => ['Unlimited jobs', 'Batch processing', 'Project save & history', 'Priority queue + exports'], 'tone' => 'ring-1 ring-cyan-300/40 pricing-card-featured'],
                ['name' => 'Agency', 'price' => '$79', 'tag' => 'For teams', 'cta' => 'Choose Agency', 'features' => ['Team seats', 'White-label ready', 'API integrations & logs', 'Dedicated onboarding flow'], 'tone' => ''],
            ] as $plan): ?>
                <div class="glass-card pricing-card p-6 <?= htmlspecialchars($plan['tone']) ?>">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm uppercase tracking-[0.3em] text-slate-400"><?= htmlspecialchars($plan['name']) ?></p>
                        <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-200"><?= htmlspecialchars($plan['tag']) ?></span>
                    </div>
                    <p class="mt-4 text-5xl font-black"><?= htmlspecialchars($plan['price']) ?><span class="text-base font-medium text-slate-400">/mo</span></p>
                    <p class="mt-3 text-sm leading-6 text-slate-300"><?= $plan['name'] === 'Starter' ? 'Perfect for testing the platform and validating your workflows.' : ($plan['name'] === 'Pro' ? 'Best balance of scale, speed, and premium workflow automation.' : 'For agencies managing multiple brands, clients, and integrations.') ?></p>
                    <ul class="mt-6 space-y-3 text-sm text-slate-300">
                        <?php foreach ($plan['features'] as $feature): ?>
                            <li class="flex items-center gap-3"><span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-400/15 text-emerald-200">✓</span> <span><?= htmlspecialchars($feature) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                    <button class="<?= $plan['name'] === 'Starter' ? 'glass-button border border-white/10' : 'primary-button' ?> mt-6 w-full"><?= htmlspecialchars($plan['cta']) ?></button>
                </div>
            <?php endforeach; ?>
        </section>

        <section id="faq" class="glass-card p-6">
            <p class="text-sm uppercase tracking-[0.3em] text-slate-400">FAQ</p>
            <div class="mt-4 grid gap-4 lg:grid-cols-3">
                <?php foreach ([
                    ['q' => 'Is the app cPanel compatible?', 'a' => 'Yes. The project uses flat PHP entry points, .htaccess routing, and a simple environment config file.'],
                    ['q' => 'Can I integrate paid APIs?', 'a' => 'Yes. Service classes include placeholders for Cloudinary, remove.bg, AI text generation, and Google APIs.'],
                    ['q' => 'Does it support FFmpeg/ImageMagick?', 'a' => 'The services detect local binaries and gracefully fall back to queued placeholders when unavailable.'],
                ] as $faq): ?>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <h3 class="font-semibold text-white"><?= htmlspecialchars($faq['q']) ?></h3>
                        <p class="mt-2 text-sm leading-6 text-slate-300"><?= htmlspecialchars($faq['a']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <footer class="border-t border-white/10 bg-slate-950/80">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-6 text-sm text-slate-400 lg:flex-row lg:items-center lg:justify-between lg:px-6">
            <p>© <?= date('Y') ?> FluxStudio Suite. Build once, deploy on cPanel, scale with APIs.</p>
            <div class="flex flex-wrap gap-4">
                <a href="dashboard.php" class="hover:text-white">Dashboard</a>
                <a href="admin.php" class="hover:text-white">Admin</a>
                <a href="analytics.php" class="hover:text-white">Analytics</a>
                <a href="install.php" class="hover:text-white">Install guide</a>
                <a href="api/seo.php" class="hover:text-white">API sample</a>
            </div>
        </div>
    </footer>

    <script src="assets/js/app.js"></script>
</body>
</html>
