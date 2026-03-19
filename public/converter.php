<?php
$module = [
    'eyebrow' => 'Universal converter',
    'title' => 'Bulk conversion workstation',
    'description' => 'Functional demo for smart format recommendations, bulk conversion, share links, QR-style text output, and file previews.',
    'features' => [
        ['icon' => '⇄', 'title' => 'AI smart suggestions', 'meta' => 'Auto recommends better target formats by media type.'],
        ['icon' => '▦', 'title' => 'Bulk converter', 'meta' => 'Queue multiple files in one request.'],
        ['icon' => '⏱', 'title' => 'Expiring share links', 'meta' => 'Generate download links with expiry windows.'],
        ['icon' => '⌁', 'title' => 'QR download text', 'meta' => 'Expose share token text for QR integrations.'],
    ],
    'content' => <<<HTML
        <div x-data="converterWidget()" x-init="init()" data-converter-root>
            <div class="grid gap-4 md:grid-cols-3">
                <select x-model="sourceType" data-converter-source class="input-surface">
                    <option value="auto">Auto detect</option>
                    <option value="image">Image</option>
                    <option value="document">Document</option>
                    <option value="video">Video</option>
                    <option value="audio">Audio</option>
                </select>
                <select x-model="targetFormat" data-converter-target class="input-surface">
                    <option value="webp">WEBP</option>
                    <option value="png">PNG</option>
                    <option value="pdf">PDF</option>
                    <option value="mp4">MP4</option>
                    <option value="mp3">MP3</option>
                </select>
                <button type="button" data-converter-trigger class="primary-button w-full">Start conversion</button>
            </div>
            <label class="upload-zone mt-6 flex min-h-[14rem] cursor-pointer flex-col items-center justify-center rounded-3xl border-2 border-dashed border-white/15 bg-slate-900/60 p-6 text-center">
                <input id="converter-files-page" data-converter-input type="file" class="hidden" multiple>
                <span class="text-4xl">⬆</span>
                <span class="mt-4 text-lg font-semibold">Upload files for conversion</span>
                <span class="mt-2 text-sm text-slate-400">If you skip uploading, demo files are used automatically.</span>
            </label>
            <div class="mt-6 h-3 overflow-hidden rounded-full bg-slate-800"><div data-converter-progress-bar class="h-full rounded-full bg-gradient-to-r from-cyan-400 to-fuchsia-500" :style="`width:${progress}%`"></div></div>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <div class="metric-card"><p class="text-xs uppercase tracking-[0.25em] text-slate-400">Queue</p><p class="mt-2 text-2xl font-bold text-white" data-converter-queue-count>1</p></div>
                <div class="metric-card"><p class="text-xs uppercase tracking-[0.25em] text-slate-400">Preset</p><p class="mt-2 text-2xl font-bold text-white" data-converter-preset>Smart</p></div>
                <div class="metric-card"><p class="text-xs uppercase tracking-[0.25em] text-slate-400">Progress</p><p class="mt-2 text-2xl font-bold text-white" data-converter-progress-label>64%</p></div>
            </div>
            <div class="mt-4 rounded-3xl border border-white/10 bg-slate-900/60 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-white">Smart preview</p>
                        <p class="mt-1 text-xs text-slate-400">Your selected file appears here before conversion.</p>
                    </div>
                    <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-slate-300" data-converter-preview-format>WEBP</span>
                </div>
                <div class="mt-4 flex items-center gap-4 rounded-2xl border border-white/10 bg-white/5 p-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-400/20 to-fuchsia-400/20 text-2xl">🖼</div>
                    <div>
                        <p class="font-medium text-white" data-converter-preview-name>preview.jpg</p>
                        <p class="text-sm text-slate-400" data-converter-preview-size>904 KB</p>
                    </div>
                </div>
            </div>
            <div data-converter-file-list class="mt-4 space-y-3"></div>
            <div data-converter-results class="mt-6 rounded-3xl border border-white/10 bg-white/5 p-5 text-slate-300">Conversion results will appear here.</div>
        </div>
    HTML,
];
require __DIR__ . '/_module_template.php';
