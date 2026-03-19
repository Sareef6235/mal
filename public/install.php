<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Guide - FluxStudio Suite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="bg-slate-950 text-white">
    <main class="mx-auto max-w-4xl px-4 py-10">
        <div class="glass-card p-6 lg:p-8">
            <p class="text-sm uppercase tracking-[0.3em] text-slate-400">Installation</p>
            <h1 class="mt-2 text-4xl font-black">Deploying on cPanel</h1>
            <ol class="mt-6 space-y-4 text-slate-300">
                <li>1. Upload the repository contents so the <code>public/</code> files live inside <code>public_html/</code>.</li>
                <li>2. Copy <code>app/config/env.example.php</code> to <code>app/config/.env.php</code> and update database/API credentials.</li>
                <li>3. Import <code>database/schema.sql</code> in phpMyAdmin.</li>
                <li>4. Import <code>database/seed_demo_user.sql</code> if you want the demo login <code>123v213@gmail.com</code> / <code>mhn1234</code>.</li>
                <li>5. Optional: import <code>database/seed_dashboard_demo.sql</code> for demo uploads, conversions, SEO logs, subscriptions, and history data.</li>
                <li>6. Ensure PHP 8.1+ is active and extensions for PDO, GD/Imagick, ZipArchive, and optionally FFmpeg are installed.</li>
                <li>7. Configure a cron job: <code>php /home/USER/public_html/scripts/cleanup.php</code> every 10 minutes.</li>
                <li>8. Enable HTTPS and verify that <code>manifest.json</code> and <code>service-worker.js</code> are publicly accessible.</li>
            </ol>
        </div>
    </main>
</body>
</html>
