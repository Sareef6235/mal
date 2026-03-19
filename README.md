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

## Quick start

1. Copy `app/config/env.example.php` to `app/config/.env.php`.
2. Update database credentials and API keys.
3. Import `database/schema.sql` into MySQL.
4. Point your domain/document root to `public/`.
5. Run `php scripts/cleanup.php` via cron every 10 minutes.
