# Notification & File Upload Management System (Core PHP + MySQL)

## Requirements
- PHP 8+
- MySQL 5.7+/8+
- Apache (cPanel shared hosting compatible)

## Project Structure
```
/config
/controllers
/models
/views
/uploads
/assets (css/js)
index.php
login.php
dashboard.php
notifications.php
database.sql
```

## Setup on cPanel
1. Upload all files to your target folder (e.g., `public_html/notifications-app`).
2. In cPanel **MySQL Databases**, ensure DB/user exist with the credentials in `config/config.php`.
3. Open **phpMyAdmin**, select `hvernued_conve`, import `database.sql`.
4. Ensure `uploads/` is writable (755 or 775 based on host policy).
5. Open `https://yourdomain.com/notifications.php` for public page.
6. Open `https://yourdomain.com/login.php` for admin page.

## Default Admin Login
- Username: `admin`
- Password: `Admin@12345`

> Change the password immediately after login by generating a new hash:
```php
<?php echo password_hash('YourStrongPassword', PASSWORD_DEFAULT); ?>
```
Then update `users.password_hash` in phpMyAdmin.

## Database Connection
Edit credentials in `config/config.php` if needed:
- DB_HOST
- DB_PORT
- DB_NAME
- DB_USER
- DB_PASS
- DB_CHARSET

All queries use PDO prepared statements.

## Security Notes
- CSRF token validation for all admin POST actions
- Session hardening (`httponly`, strict mode, regenerate on login)
- XSS-safe output with `htmlspecialchars`
- Upload size enforced to max 2MB
- Files renamed uniquely to avoid overwrite
- PHP execution blocked inside `/uploads/` via `.htaccess`

## Linking From Another Website
Use either direct file path or friendly route:
```html
<a href="https://yourdomain.com/notifications.php">Open Notifications</a>
```
Or if `.htaccess` is enabled:
```html
<a href="https://yourdomain.com/notifications">Open Notifications</a>
```

## Optional Enhancements Included
- Search notifications in dashboard
- Toast-style success/error messages
- Mobile responsive card UI
