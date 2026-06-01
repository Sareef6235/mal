CREATE TABLE IF NOT EXISTS regex_rules (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  pattern TEXT NOT NULL,
  flags VARCHAR(20) NOT NULL DEFAULT 'miu',
  target_fields JSON NULL,
  priority INT NOT NULL DEFAULT 100,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  sample_text MEDIUMTEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_regex_active_priority (is_active, priority),
  INDEX idx_regex_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ocr_imports (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  mime_type VARCHAR(120) NOT NULL,
  file_size BIGINT UNSIGNED NOT NULL,
  extracted_text LONGTEXT NULL,
  regex_rule_id INT UNSIGNED NULL,
  rows_found INT UNSIGNED NOT NULL DEFAULT 0,
  rows_ready INT UNSIGNED NOT NULL DEFAULT 0,
  rows_failed INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('uploaded','extracted','parsed','draft','imported','failed','cancelled') NOT NULL DEFAULT 'uploaded',
  error_message TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ocr_import_regex FOREIGN KEY (regex_rule_id) REFERENCES regex_rules(id) ON DELETE SET NULL,
  INDEX idx_ocr_status_created (status, created_at),
  INDEX idx_ocr_original_name (original_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ocr_import_rows (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  import_id BIGINT UNSIGNED NOT NULL,
  row_index INT UNSIGNED NOT NULL,
  month VARCHAR(30) NULL,
  class_name VARCHAR(100) NULL,
  week VARCHAR(50) NULL,
  total_period VARCHAR(50) NULL,
  subject VARCHAR(150) NULL,
  lesson_name VARCHAR(255) NULL,
  lesson_details TEXT NULL,
  activities TEXT NULL,
  smart_date DATE NULL,
  exam_date DATE NULL,
  raw_match MEDIUMTEXT NULL,
  validation_errors JSON NULL,
  status ENUM('ready','failed','imported','draft') NOT NULL DEFAULT 'ready',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ocr_rows_import FOREIGN KEY (import_id) REFERENCES ocr_imports(id) ON DELETE CASCADE,
  INDEX idx_ocr_rows_import_status (import_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ocr_settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value TEXT NULL,
  setting_type ENUM('string','integer','boolean','json') NOT NULL DEFAULT 'string',
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ocr_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  import_id BIGINT UNSIGNED NULL,
  level ENUM('debug','info','warning','error') NOT NULL DEFAULT 'info',
  message TEXT NOT NULL,
  context JSON NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ocr_logs_import FOREIGN KEY (import_id) REFERENCES ocr_imports(id) ON DELETE SET NULL,
  INDEX idx_ocr_logs_level_created (level, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS monthly_plan (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  import_row_id BIGINT UNSIGNED NULL,
  month VARCHAR(30) NOT NULL,
  class_name VARCHAR(100) NOT NULL,
  week VARCHAR(50) NULL,
  total_period VARCHAR(50) NULL,
  subject VARCHAR(150) NOT NULL,
  lesson_name VARCHAR(255) NOT NULL,
  lesson_details TEXT NULL,
  activities TEXT NULL,
  smart_date DATE NULL,
  exam_date DATE NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_monthly_plan_import_row FOREIGN KEY (import_row_id) REFERENCES ocr_import_rows(id) ON DELETE SET NULL,
  INDEX idx_monthly_plan_lookup (month, class_name, subject),
  INDEX idx_monthly_plan_dates (smart_date, exam_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ocr_settings (setting_key, setting_value, setting_type) VALUES
('ocr_engine','tesseract','string'),
('auto_cleanup','1','boolean'),
('remove_extra_spaces','1','boolean'),
('auto_detect_tables','1','boolean'),
('confidence_level','70','integer'),
('default_regex_id','','string'),
('fallback_regex_id','','string'),
('multi_regex_processing','1','boolean'),
('webhook_url','','string'),
('file_retention_days','30','integer'),
('maximum_upload_size','10485760','integer'),
('debug_mode','0','boolean')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type);
