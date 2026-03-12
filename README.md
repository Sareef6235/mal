# 🕌 നിസ്കാരം ട്രാക്കർ (Prayer Tracker & Student Management System)

## cPanel Installation
1. Create MySQL database and user in cPanel.
2. Import `schema.sql` using phpMyAdmin.
3. Upload all files/folders into `public_html/`.
4. Update database credentials in `config/db.php`.
5. Ensure PHP 8+ is enabled.
6. Open `https://yourdomain.com/login.php`.

## Default Credentials
- Admin: `admin` / `admin1`
- Teacher: `teacher` / `teacher1`

## Folder Structure
- `/config` PDO, auth, csrf bootstrap
- `/admin` admin dashboard and management
- `/teacher` teacher pages
- `/student` student pages
- Root pages (`index.php`, `tracker.php`, etc.)
