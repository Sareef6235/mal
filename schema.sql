CREATE DATABASE IF NOT EXISTS saas_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE saas_dashboard;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  category_id INT NOT NULL,
  status ENUM('pending', 'in_progress', 'done') NOT NULL DEFAULT 'pending',
  due_date DATE NOT NULL,
  sort_order INT NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_items_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

INSERT INTO categories (name) VALUES
('Hifz'), ('Nazra'), ('Homework'), ('Monthly Plan')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO users (name, email, password_hash, role)
VALUES ('Super Admin', 'admin@example.com', '$2y$12$tP7MHAGC5zB47dnL2d4qs.mXRVpMbEHwUuCz01PFk77z4Yx5C9wFq', 'admin')
ON DUPLICATE KEY UPDATE email = VALUES(email);

INSERT INTO items (title, category_id, status, due_date, sort_order)
SELECT 'Weekly Tajweed Review', 2, 'in_progress', CURDATE() + INTERVAL 7 DAY, 1
WHERE NOT EXISTS (SELECT 1 FROM items);
