# FluxStudio Suite

FluxStudio Suite is a cPanel-ready PHP 8+ SaaS starter that combines a universal file converter, advanced image editor, video/document tooling, and an SEO toolkit in one responsive web app.

## Features

- Universal converter UI for image, document, video, and audio workflows.
- Advanced image editor module layout for resize, compression, crop, watermark, background removal, and AI-ready enhancement.
- SEO toolkit endpoint for meta description, robots.txt, sitemap hints, keyword density, internal link estimates, and blog outlines.
- Secure auth starter with CSRF protection, session-based login, and MySQL schema.
- PWA manifest + service worker, glassmorphism UI, dark mode, and cPanel deployment guide.

## Structure

- `public/` - web root for cPanel `public_html`
- `app/` - helpers, config, services, storage
- `database/` - MySQL schema
- `scripts/` - maintenance utilities such as cleanup cron jobs

## Included module pages

- `public/converter.php` - working bulk conversion demo with smart recommendations, share links, and downloads
- `public/image-editor.php` - image editor workspace page
- `public/document-tools.php` - document tools page
- `public/video-tools.php` - FFmpeg-ready video tools page
- `public/seo-tools.php` - dedicated SEO toolkit page
- `public/projects.php`, `public/downloads.php`, `public/admin.php`, `public/analytics.php` - saved projects, delivery, admin, and analytics areas

## Quick start

1. Copy `app/config/env.example.php` to `app/config/.env.php`.
2. Update database credentials and API keys.
3. Import `database/schema.sql` into MySQL.
4. Optional: import `database/seed_demo_user.sql` to add the demo login `123v213@gmail.com` with password `mhn1234` (stored hashed in the database).
5. Point your domain/document root to `public/`.
6. Run `php scripts/cleanup.php` via cron every 10 minutes.
