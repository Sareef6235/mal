# Smart Madrasa Attendance (PHP + MySQL)

## Run locally

1. Create DB and import schema:

```sql
CREATE DATABASE madrasa_attendance;
USE madrasa_attendance;

CREATE TABLE students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  register_no VARCHAR(50) NOT NULL UNIQUE,
  class VARCHAR(50) NOT NULL,
  face_image VARCHAR(255) NULL,
  qr_code VARCHAR(255) NULL
);

CREATE TABLE attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  date DATE NOT NULL,
  time TIME NOT NULL,
  method ENUM('face','qr') NOT NULL,
  photo VARCHAR(255) NULL,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);
```

2. Configure DB environment variables if needed (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).
3. Run:

```bash
php -S 0.0.0.0:8000
```

4. Open `http://localhost:8000`.

## Demo users

- admin / admin123
- teacher / teacher123
- student / student123
