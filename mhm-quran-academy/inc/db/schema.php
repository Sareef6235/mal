<?php
if (!defined('ABSPATH')) { exit; }

add_action('after_switch_theme', function () {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset = $wpdb->get_charset_collate();

    $sql = [];
    $sql[] = "CREATE TABLE {$wpdb->prefix}quran_surahs (id SMALLINT UNSIGNED PRIMARY KEY, name_ar VARCHAR(120) NOT NULL, name_en VARCHAR(120) NOT NULL, total_ayahs SMALLINT UNSIGNED NOT NULL, revelation_type VARCHAR(20) NOT NULL, KEY name_en_idx (name_en)) $charset;";
    $sql[] = "CREATE TABLE {$wpdb->prefix}quran_ayahs (id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT, surah_id SMALLINT UNSIGNED NOT NULL, ayah_number SMALLINT UNSIGNED NOT NULL, arabic_text LONGTEXT NOT NULL, page SMALLINT UNSIGNED, juz SMALLINT UNSIGNED, hizb SMALLINT UNSIGNED, sajda TINYINT DEFAULT 0, UNIQUE KEY surah_ayah (surah_id,ayah_number), KEY surah_idx (surah_id), CONSTRAINT fk_ayah_surah FOREIGN KEY (surah_id) REFERENCES {$wpdb->prefix}quran_surahs(id) ON DELETE CASCADE) $charset;";
    $sql[] = "CREATE TABLE {$wpdb->prefix}quran_translations (id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT, ayah_id BIGINT UNSIGNED NOT NULL, lang VARCHAR(16) NOT NULL, translation LONGTEXT, transliteration LONGTEXT, KEY ayah_lang (ayah_id,lang), CONSTRAINT fk_trans_ayah FOREIGN KEY (ayah_id) REFERENCES {$wpdb->prefix}quran_ayahs(id) ON DELETE CASCADE) $charset;";
    $sql[] = "CREATE TABLE {$wpdb->prefix}quran_audio (id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT, ayah_id BIGINT UNSIGNED NOT NULL, reciter VARCHAR(100) NOT NULL, audio_url TEXT NOT NULL, duration FLOAT, waveform JSON, KEY ayah_reciter (ayah_id,reciter), CONSTRAINT fk_audio_ayah FOREIGN KEY (ayah_id) REFERENCES {$wpdb->prefix}quran_ayahs(id) ON DELETE CASCADE) $charset;";
    $sql[] = "CREATE TABLE {$wpdb->prefix}quran_tajweed (id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT, ayah_id BIGINT UNSIGNED NOT NULL, rule_type VARCHAR(40), start_pos SMALLINT UNSIGNED, end_pos SMALLINT UNSIGNED, tooltip TEXT, KEY ayah_rule (ayah_id,rule_type), CONSTRAINT fk_tajweed_ayah FOREIGN KEY (ayah_id) REFERENCES {$wpdb->prefix}quran_ayahs(id) ON DELETE CASCADE) $charset;";
    $sql[] = "CREATE TABLE {$wpdb->prefix}quran_bookmarks (id BIGINT PRIMARY KEY AUTO_INCREMENT, user_id BIGINT, ayah_id BIGINT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $charset;";
    $sql[] = "CREATE TABLE {$wpdb->prefix}quran_progress (id BIGINT PRIMARY KEY AUTO_INCREMENT, user_id BIGINT, surah_id SMALLINT, ayah_number SMALLINT, streak_days INT DEFAULT 0, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) $charset;";
    $sql[] = "CREATE TABLE {$wpdb->prefix}quran_notes (id BIGINT PRIMARY KEY AUTO_INCREMENT, user_id BIGINT, ayah_id BIGINT, note LONGTEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $charset;";

    $sql[] = "CREATE TABLE {$wpdb->prefix}quran_tajweed_rules (id BIGINT PRIMARY KEY AUTO_INCREMENT, rule_key VARCHAR(50) UNIQUE, title VARCHAR(190), description TEXT, color_hex VARCHAR(10), difficulty VARCHAR(20), kids_tip TEXT, pronunciation_guide TEXT, audio_url TEXT, sort_order INT DEFAULT 0) $charset;";
    $sql[] = "CREATE TABLE {$wpdb->prefix}quran_tajweed_examples (id BIGINT PRIMARY KEY AUTO_INCREMENT, rule_id BIGINT, ayah_id BIGINT NULL, sample_text LONGTEXT, explanation TEXT, start_pos SMALLINT, end_pos SMALLINT, audio_url TEXT, KEY rule_idx (rule_id)) $charset;";
    $sql[] = "CREATE TABLE {$wpdb->prefix}quran_tajweed_quiz (id BIGINT PRIMARY KEY AUTO_INCREMENT, question TEXT, options LONGTEXT, correct_index TINYINT, rule_key VARCHAR(50), difficulty VARCHAR(20), created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $charset;";
    $sql[] = "CREATE TABLE {$wpdb->prefix}quran_quizzes (id BIGINT PRIMARY KEY AUTO_INCREMENT, title VARCHAR(190), quiz_data LONGTEXT, level VARCHAR(30), created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $charset;";

    $sql[] = "CREATE TABLE {$wpdb->prefix}quran_reciters (id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT, slug VARCHAR(80) UNIQUE, name VARCHAR(120) NOT NULL, language VARCHAR(20) DEFAULT 'ar', bitrate SMALLINT DEFAULT 128, created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $charset;";

    foreach ($sql as $statement) { dbDelta($statement); }
});
