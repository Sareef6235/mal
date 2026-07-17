-- New tables only. Existing attendance tables are read-only data sources.
CREATE TABLE IF NOT EXISTS document_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS document_templates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, category_id BIGINT UNSIGNED NULL, name VARCHAR(160) NOT NULL,
  description TEXT NULL, version VARCHAR(30) NOT NULL DEFAULT '1.0', original_name VARCHAR(255) NOT NULL,
  storage_path VARCHAR(500) NOT NULL, status ENUM('active','inactive','archived') NOT NULL DEFAULT 'active',
  created_by BIGINT UNSIGNED NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_document_template_category FOREIGN KEY (category_id) REFERENCES document_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS document_placeholders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, template_id BIGINT UNSIGNED NOT NULL, placeholder VARCHAR(120) NOT NULL,
  source_table VARCHAR(64) NULL, source_column VARCHAR(64) NULL, transform_rule VARCHAR(255) NULL,
  mapping_status ENUM('mapped','unmapped','custom','system') NOT NULL DEFAULT 'unmapped', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_template_placeholder (template_id, placeholder),
  CONSTRAINT fk_placeholder_template FOREIGN KEY (template_id) REFERENCES document_templates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS generated_documents (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, template_id BIGINT UNSIGNED NOT NULL, file_name VARCHAR(255) NOT NULL,
  storage_path VARCHAR(500) NOT NULL, output_type ENUM('docx','pdf','zip') NOT NULL DEFAULT 'docx', member_count INT UNSIGNED NOT NULL DEFAULT 1,
  file_size BIGINT UNSIGNED NOT NULL DEFAULT 0, status ENUM('completed','failed','processing') NOT NULL DEFAULT 'processing',
  generated_by BIGINT UNSIGNED NULL, download_count INT UNSIGNED NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_generated_template FOREIGN KEY (template_id) REFERENCES document_templates(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS document_audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, actor_id BIGINT UNSIGNED NULL, action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(80) NOT NULL, entity_id BIGINT UNSIGNED NULL, details JSON NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_document_audit_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
