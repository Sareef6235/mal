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
        <div x-data="converterWidget()">
            <div class="grid gap-4 md:grid-cols-3">
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
                    <option value="pdf">PDF</option>
                    <option value="mp4">MP4</option>
                    <option value="mp3">MP3</option>
                </select>
                <button @click="startConversionRequest()" class="primary-button w-full">Start conversion</button>
            </div>
            <label class="upload-zone mt-6 flex min-h-[14rem] cursor-pointer flex-col items-center justify-center rounded-3xl border-2 border-dashed border-white/15 bg-slate-900/60 p-6 text-center">
                <input id="converter-files-page" type="file" class="hidden" multiple @change="loadFiles($event)">
                <span class="text-4xl">⬆</span>
                <span class="mt-4 text-lg font-semibold">Upload files for conversion</span>
                <span class="mt-2 text-sm text-slate-400">If you skip uploading, demo files are used automatically.</span>
            </label>
            <div class="mt-6 h-3 overflow-hidden rounded-full bg-slate-800"><div class="h-full rounded-full bg-gradient-to-r from-cyan-400 to-fuchsia-500" :style="`width:${progress}%`"></div></div>
            <div id="conversion-results" class="mt-6 rounded-3xl border border-white/10 bg-white/5 p-5 text-slate-300">Conversion results will appear here.</div>
        </div>
    HTML,
];
require __DIR__ . '/_module_template.php';
