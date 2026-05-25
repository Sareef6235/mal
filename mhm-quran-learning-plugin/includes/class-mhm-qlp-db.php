<?php
if (!defined('ABSPATH')) { exit; }

class MHM_QLP_DB {
    public static function activate(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $p = $wpdb->prefix;
        $sql = [];
        $sql[] = "CREATE TABLE {$p}qlp_surahs (id SMALLINT UNSIGNED PRIMARY KEY, name_ar VARCHAR(120) NOT NULL, name_en VARCHAR(120) NOT NULL, total_ayahs SMALLINT UNSIGNED NOT NULL, revelation_type VARCHAR(20) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $charset;";
        $sql[] = "CREATE TABLE {$p}qlp_ayahs (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, surah_id SMALLINT UNSIGNED NOT NULL, ayah_number SMALLINT UNSIGNED NOT NULL, arabic_text LONGTEXT NOT NULL, transliteration LONGTEXT NULL, translation LONGTEXT NULL, juz SMALLINT UNSIGNED NULL, hizb SMALLINT UNSIGNED NULL, page SMALLINT UNSIGNED NULL, UNIQUE KEY surah_ayah (surah_id, ayah_number), KEY surah_idx (surah_id)) $charset;";
        $sql[] = "CREATE TABLE {$p}qlp_audio (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ayah_id BIGINT UNSIGNED NOT NULL, reciter VARCHAR(120) NOT NULL, audio_url TEXT NOT NULL, duration FLOAT NULL, KEY ayah_reciter (ayah_id, reciter(40))) $charset;";
        $sql[] = "CREATE TABLE {$p}qlp_tajweed_rules (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ayah_id BIGINT UNSIGNED NOT NULL, rule_type VARCHAR(50) NOT NULL, start_pos SMALLINT UNSIGNED NOT NULL, end_pos SMALLINT UNSIGNED NOT NULL, rule_note TEXT NULL, KEY ayah_idx (ayah_id)) $charset;";
        $sql[] = "CREATE TABLE {$p}qlp_tafsir (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ayah_id BIGINT UNSIGNED NOT NULL, source VARCHAR(120) NOT NULL, lang VARCHAR(10) NOT NULL DEFAULT 'en', tafsir_text LONGTEXT NOT NULL, KEY ayah_lang (ayah_id, lang)) $charset;";
        $sql[] = "CREATE TABLE {$p}qlp_word_timings (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ayah_id BIGINT UNSIGNED NOT NULL, word_index SMALLINT UNSIGNED NOT NULL, word_text VARCHAR(255) NOT NULL, start_ms INT UNSIGNED NOT NULL, end_ms INT UNSIGNED NOT NULL, KEY ayah_word (ayah_id, word_index)) $charset;";
        $sql[] = "CREATE TABLE {$p}qlp_progress (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, surah_id SMALLINT UNSIGNED NOT NULL, ayah_number SMALLINT UNSIGNED NOT NULL, streak_days INT UNSIGNED DEFAULT 0, completion_pct DECIMAL(5,2) DEFAULT 0, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, KEY user_idx (user_id)) $charset;";
        $sql[] = "CREATE TABLE {$p}qlp_bookmarks (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, ayah_id BIGINT UNSIGNED NOT NULL, note VARCHAR(255) NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, KEY user_ayah (user_id, ayah_id)) $charset;";
        $sql[] = "CREATE TABLE {$p}qlp_quizzes (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(200) NOT NULL, quiz_json LONGTEXT NOT NULL, level VARCHAR(30) DEFAULT 'beginner', created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $charset;";
        foreach ($sql as $q) { dbDelta($q); }
    }
}
