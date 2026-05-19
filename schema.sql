CREATE TABLE IF NOT EXISTS ustads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(255) NULL,
  email_locked TINYINT(1) NOT NULL DEFAULT 0,
  otp_code VARCHAR(10) NULL,
  otp_expires DATETIME NULL,
  reset_verified TINYINT(1) NOT NULL DEFAULT 0,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  remember_token VARCHAR(255) NULL,
  last_login DATETIME NULL
);
