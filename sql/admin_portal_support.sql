-- Support schema for admin_portal.php, self_card_bulk.php, idcard_bulk.php, admin_cards.php

CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(120) PRIMARY KEY,
  setting_value TEXT
);

CREATE TABLE IF NOT EXISTS self_profiles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(191) NOT NULL,
  msr_no VARCHAR(100) NOT NULL UNIQUE,
  address VARCHAR(255) NOT NULL,
  place VARCHAR(191) NOT NULL,
  work_madrasa VARCHAR(191) NOT NULL,
  phone VARCHAR(50) NOT NULL,
  qr_token VARCHAR(64) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Optional compatibility columns for mixed code paths
ALTER TABLE students ADD COLUMN name VARCHAR(191) NULL;
ALTER TABLE students ADD COLUMN class_id INT NULL;
ALTER TABLE students ADD COLUMN class_rank INT NULL;
ALTER TABLE students ADD COLUMN overall_rank INT NULL;

-- Ensure settings defaults
INSERT INTO settings(setting_key, setting_value) VALUES
('principal_name', 'Principal'),
('site_title', 'Premium Madrasa Student Result Management System')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);
