# Premium Support Desk

A PHP + MySQL support ticket system with:

- responsive premium landing page and navigation menu
- secure customer registration and admin login
- MySQL schema for users, tickets, messages, and notifications
- WhatsApp-style chat UI for ticket replies
- email and WhatsApp notification queue integration hooks
- order reference linking inside every ticket

## Run locally

1. Create a MySQL database and import `schema.sql`.
2. Update credentials in `config.php`.
3. Start PHP built-in server:

   ```bash
   php -S 127.0.0.1:8000
   ```

4. Open `http://127.0.0.1:8000`.

## Default admin

- Email: `123v213@gmail.com`
- Password: `mhn1234`

The admin user is auto-seeded on first successful database connection.
