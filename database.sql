CREATE DATABASE IF NOT EXISTS mal_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mal_dashboard;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(40) NULL,
    password_hash VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) NULL,
    cover_photo VARCHAR(255) NULL,
    bio TEXT NULL,
    address VARCHAR(255) NULL,
    gender VARCHAR(40) NULL,
    date_of_birth DATE NULL,
    website VARCHAR(255) NULL,
    twitter VARCHAR(255) NULL,
    linkedin VARCHAR(255) NULL,
    github VARCHAR(255) NULL,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('active','pending','suspended','deleted') NOT NULL DEFAULT 'active',
    membership_plan VARCHAR(80) NOT NULL DEFAULT 'Starter',
    storage_used_mb INT UNSIGNED NOT NULL DEFAULT 0,
    storage_limit_mb INT UNSIGNED NOT NULL DEFAULT 5120,
    rank_title VARCHAR(80) NOT NULL DEFAULT 'Explorer',
    last_login_at DATETIME NULL,
    last_login_ip VARCHAR(45) NULL,
    two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
    email_verified_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_status (status),
    INDEX idx_users_username (username)
) ENGINE=InnoDB;

CREATE TABLE login_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    device_name VARCHAR(160) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    location VARCHAR(120) NULL,
    user_agent VARCHAR(255) NULL,
    logged_in_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_login_user_time (user_id, logged_in_at)
) ENGINE=InnoDB;

CREATE TABLE user_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    theme ENUM('dark','light','system') NOT NULL DEFAULT 'dark',
    language VARCHAR(12) NOT NULL DEFAULT 'en',
    email_notifications TINYINT(1) NOT NULL DEFAULT 1,
    push_notifications TINYINT(1) NOT NULL DEFAULT 1,
    marketing_notifications TINYINT(1) NOT NULL DEFAULT 0,
    profile_visibility ENUM('public','members','private') NOT NULL DEFAULT 'members',
    show_online_status TINYINT(1) NOT NULL DEFAULT 1,
    allow_download_tracking TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE achievements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(120) NOT NULL,
    icon VARCHAR(20) NOT NULL DEFAULT '🏆',
    description VARCHAR(255) NULL,
    earned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_achievements_user (user_id, earned_at)
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(160) NOT NULL,
    body TEXT NULL,
    type ENUM('info','success','warning','security') NOT NULL DEFAULT 'info',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notifications_user (user_id, is_read, created_at)
) ENGINE=InnoDB;

CREATE TABLE favorites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    material_id BIGINT UNSIGNED NULL,
    title VARCHAR(180) NOT NULL,
    url VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_favorites_user (user_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE downloads (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    material_id BIGINT UNSIGNED NULL,
    title VARCHAR(180) NOT NULL,
    file_size_mb DECIMAL(10,2) NOT NULL DEFAULT 0,
    downloaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_downloads_user (user_id, downloaded_at)
) ENGINE=InnoDB;

CREATE TABLE quiz_results (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    quiz_title VARCHAR(180) NOT NULL,
    score DECIMAL(5,2) NOT NULL,
    total_questions INT UNSIGNED NOT NULL DEFAULT 0,
    correct_answers INT UNSIGNED NOT NULL DEFAULT 0,
    duration_seconds INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_quiz_user (user_id, created_at)
) ENGINE=InnoDB;

INSERT INTO users (id, full_name, username, email, phone, password_hash, bio, address, gender, date_of_birth, website, twitter, linkedin, github, is_verified, membership_plan, storage_used_mb, storage_limit_mb, rank_title, last_login_at, last_login_ip, two_factor_enabled, email_verified_at)
VALUES (1, 'Avery Stone', 'avery.ai', 'avery@example.com', '+1 415 555 0198', '$2y$10$wHh0wYFknrtuyb0NqH9ew.XfSGA4MVTPqPpif1vQyN.3J6mM.rW4W', 'Building beautiful learning systems with AI, analytics, and modern product craft.', 'San Francisco, CA', 'Non-binary', '1998-06-21', 'https://example.com', 'https://x.com/example', 'https://linkedin.com/in/example', 'https://github.com/example', 1, 'Quantum Pro', 8240, 20480, 'Diamond Learner', NOW(), '127.0.0.1', 1, NOW())
ON DUPLICATE KEY UPDATE email = VALUES(email);

INSERT INTO user_settings (user_id, theme, language, email_notifications, push_notifications, marketing_notifications, profile_visibility) VALUES
(1, 'dark', 'en', 1, 1, 0, 'members')
ON DUPLICATE KEY UPDATE theme = VALUES(theme);
