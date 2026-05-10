-- Public Study Materials Dashboard + Admin Upload System schema
-- Import with: mysql -u USER -p DATABASE < database.sql

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','student') NOT NULL DEFAULT 'student',
    avatar VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    slug VARCHAR(140) NOT NULL UNIQUE,
    color VARCHAR(20) NOT NULL DEFAULT '#4f46e5',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS classes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    slug VARCHAR(140) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS materials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(220) NOT NULL,
    slug VARCHAR(240) NOT NULL UNIQUE,
    description TEXT NULL,
    subject_id INT UNSIGNED NOT NULL,
    class_id INT UNSIGNED NOT NULL,
    tags VARCHAR(500) NULL,
    file_name VARCHAR(255) NOT NULL,
    original_file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type ENUM('pdf','doc','image','video','zip','other') NOT NULL DEFAULT 'other',
    mime_type VARCHAR(120) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
    thumbnail_path VARCHAR(500) NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_premium TINYINT(1) NOT NULL DEFAULT 0,
    views_count INT UNSIGNED NOT NULL DEFAULT 0,
    downloads_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FULLTEXT KEY ft_materials (title, description, tags),
    INDEX idx_materials_filter (is_published, subject_id, class_id, file_type, created_at),
    CONSTRAINT fk_material_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT,
    CONSTRAINT fk_material_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE RESTRICT,
    CONSTRAINT fk_material_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS downloads (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    material_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_download_material_date (material_id, created_at),
    CONSTRAINT fk_download_material FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE,
    CONSTRAINT fk_download_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS views (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    material_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_view_material_date (material_id, created_at),
    CONSTRAINT fk_view_material FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE,
    CONSTRAINT fk_view_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS favorites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    material_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    session_id VARCHAR(128) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_favorite_user (material_id, user_id),
    INDEX idx_favorite_session (session_id),
    CONSTRAINT fk_favorite_material FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE,
    CONSTRAINT fk_favorite_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO subjects (name, slug, color) VALUES
('Mathematics', 'mathematics', '#06b6d4'),
('Physics', 'physics', '#7c3aed'),
('Chemistry', 'chemistry', '#22c55e'),
('Computer Science', 'computer-science', '#f59e0b'),
('English', 'english', '#ef4444');

INSERT IGNORE INTO classes (name, slug) VALUES
('Grade 9', 'grade-9'), ('Grade 10', 'grade-10'), ('Grade 11', 'grade-11'), ('Grade 12', 'grade-12'), ('University', 'university');
