# Madras Fee Management System

## Setup
1. Create database and tables using `schema.sql`.
2. Generate hashed password and replace `{PASSWORD_HASH}` in schema:
   ```php
   <?php echo password_hash('admin123', PASSWORD_DEFAULT); ?>
   ```
3. Update DB credentials in `config.php`.
4. Upload all files to cPanel `public_html` and import schema.

## Default login
- username: `superadmin`
- password: value used when generating hash.
