# Lumina Study Cloud

A premium PHP + MySQL public study materials dashboard with a connected admin upload and management system.

## Features

- Public materials dashboard with live AJAX search, subject/class filters, sorting, previews, favorites, share links, trending and recent sections.
- Admin upload studio with drag-and-drop multi-file uploads, AJAX progress, thumbnail support, publish/featured/premium toggles, duplicate file prevention, edit/delete tools, and subject/class management.
- Secure backend patterns: PDO prepared statements, CSRF tokens, escaped output, session-based admin login, validated uploads, and upload execution blocking.
- Responsive SaaS UI with Bootstrap 5, glassmorphism, neon gradients, mobile bottom navigation, sticky sidebar, SweetAlert2 toasts, skeleton loading, and micro-interactions.

## Setup

1. Create a MySQL database and import `database.sql`.
2. Configure connection details with `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` environment variables or edit `config.php`.
3. Create an admin user:

```sql
INSERT INTO users (name, email, password_hash, role)
VALUES ('Admin', 'admin@example.com', '$2y$10$replace_with_password_hash', 'admin');
```

Generate the hash with:

```bash
php -r "echo password_hash('your-password', PASSWORD_DEFAULT), PHP_EOL;"
```

4. Ensure the web server can write to `uploads/`.
5. Open `index.php` for the public portal and `admin.php` for the upload studio.
