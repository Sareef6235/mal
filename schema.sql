CREATE DATABASE IF NOT EXISTS madras_fee_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE madras_fee_db;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('teacher','admin','super_admin') NOT NULL DEFAULT 'teacher'
);

CREATE TABLE IF NOT EXISTS students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  class VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS fees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  class VARCHAR(50) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  payment_method ENUM('Cash','GPay','PhonePe','UPI') NOT NULL,
  date DATE NOT NULL,
  status ENUM('Pending','Verified') NOT NULL DEFAULT 'Pending',
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

INSERT INTO users (name, username, password, role)
VALUES ('Super Admin', 'superadmin', '{PASSWORD_HASH}', 'super_admin');
