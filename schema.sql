CREATE DATABASE IF NOT EXISTS mhm_sksbv_notice CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mhm_sksbv_notice;

CREATE TABLE IF NOT EXISTS notices (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title_malayalam VARCHAR(255) NOT NULL,
  title_arabic VARCHAR(255) NOT NULL,
  sub_title VARCHAR(255) DEFAULT '',
  main_topic VARCHAR(255) DEFAULT '',
  notice_date VARCHAR(80) DEFAULT '',
  notice_time VARCHAR(80) DEFAULT '',
  venue VARCHAR(255) DEFAULT '',
  qiraat VARCHAR(255) DEFAULT '',
  welcome VARCHAR(255) DEFAULT '',
  president VARCHAR(255) DEFAULT '',
  inaugurator VARCHAR(255) DEFAULT '',
  translation_speech VARCHAR(255) DEFAULT '',
  speakers TEXT,
  singers TEXT,
  thanks VARCHAR(255) DEFAULT '',
  association_stamp VARCHAR(255) DEFAULT '',
  bg_style VARCHAR(40) DEFAULT 'emerald',
  design_style VARCHAR(40) DEFAULT 'classic',
  font_style_malayalam VARCHAR(40) DEFAULT 'manjari',
  font_style_english VARCHAR(40) DEFAULT 'inter',
  font_style_arabic VARCHAR(40) DEFAULT 'amiri',
  font_size_settings VARCHAR(40) DEFAULT 'normal',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_notice_date (notice_date),
  FULLTEXT KEY ft_notice_search (title_malayalam, title_arabic, sub_title, main_topic, venue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  notice_id INT UNSIGNED NOT NULL,
  class_num VARCHAR(40) NOT NULL,
  student_name VARCHAR(255) NOT NULL,
  item_type VARCHAR(255) NOT NULL,
  item_icon VARCHAR(16) DEFAULT '🟢',
  position_order INT UNSIGNED DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_student_notice FOREIGN KEY (notice_id) REFERENCES notices(id) ON DELETE CASCADE,
  INDEX idx_notice_class (notice_id, class_num)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
