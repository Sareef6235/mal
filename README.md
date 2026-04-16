# SaaS Dashboard (PHP + MySQL + Tailwind)

## Folder Structure

- `public_html/index.html`
- `public_html/login.html`
- `public_html/dashboard.html`
- `public_html/app.js`
- `public_html/styles.css`
- `public_html/manifest.json`
- `public_html/service-worker.js`
- `public_html/api/*.php`
- `public_html/assets/icons/*`
- `schema.sql`

## cPanel Setup

1. Create a MySQL database and user in cPanel.
2. Import `schema.sql` using phpMyAdmin.
3. Update DB credentials in `public_html/api/db.php`:
   - `DB_HOST`
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASS`
4. Upload all files preserving structure so `/public_html` is your web root.
5. Ensure PHP 8.0+ is enabled.
6. Visit `/login.html`.

## Default Demo Login

- Email: `admin@example.com`
- Password: `admin123`

## Notes

- Uses session-based authentication.
- All API endpoints return JSON.
- Fully works without Node.js or build tooling.
- Uses CDN-only dependencies (Tailwind, Chart.js, SortableJS, jsPDF).
