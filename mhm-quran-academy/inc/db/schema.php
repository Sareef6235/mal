<?php
if (!defined('ABSPATH')) { exit; }

add_action('after_switch_theme', function () {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset = $wpdb->get_charset_collate();

    $sql = [];
    $sql[] = "CREATE TABLE {$wpdb->prefix}quran_surahs (id SMALLINT PRIMARY KEY, name_ar VARCHAR(120), name_en VARCHAR(120), total_ayahs SMALLINT, revelation_type VARCHAR(20)) $charset;";
    $sql[] = "CREATE TABLE {$wpdb->prefix}quran_ayahs (id BIGINT PRIMARY KEY AUTO_INCREMENT, surah_id SMALLINT, ayah_number SMALLINT, arabic_text LONGTEXT, page SMALLINT, juz SMALLINT, hizb SMALLINT, sajda TINYINT DEFAULT 0, UNIQUE KEY surah_ayah (surah_id,ayah_number)) $charset;";
    $sql[] = "CREATE TABLE {$wpdb->prefix}quran_translations (id BIGINT PRIMARY KEY AUTO_INCREMENT, ayah_id BIGINT, lang VARCHAR(16), translation LONGTEXT, transliteration LONGTEXT, KEY ayah_lang (ayah_id,lang)) $charset;";
    $sql[] = "CREATE TABLE {$wpdb->prefix}quran_audio (id BIGINT PRIMARY KEY AUTO_INCREMENT, ayah_id BIGINT, reciter VARCHAR(100), audio_url TEXT, duration FLOAT, waveform JSON, KEY ayah_reciter (ayah_id,reciter)) $charset;";
    $sql[] = "CREATE TABLE {$wpdb->prefix}quran_tajweed (id BIGINT PRIMARY KEY AUTO_INCREMENT, ayah_id BIGINT, rule_type VARCHAR(40), start_pos SMALLINT, end_pos SMALLINT, tooltip TEXT) $charset;";
    $sql[] = "CREATE TABLE {$wpdb->prefix}quran_bookmarks (id BIGINT PRIMARY KEY AUTO_INCREMENT, user_id BIGINT, ayah_id BIGINT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $charset;";
    $sql[] = "CREATE TABLE {$wpdb->prefix}quran_progress (id BIGINT PRIMARY KEY AUTO_INCREMENT, user_id BIGINT, surah_id SMALLINT, ayah_number SMALLINT, streak_days INT DEFAULT 0, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) $charset;";
    $sql[] = "CREATE TABLE {$wpdb->prefix}quran_notes (id BIGINT PRIMARY KEY AUTO_INCREMENT, user_id BIGINT, ayah_id BIGINT, note LONGTEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $charset;";
    $sql[] = "CREATE TABLE {$wpdb->prefix}quran_quizzes (id BIGINT PRIMARY KEY AUTO_INCREMENT, title VARCHAR(190), quiz_data LONGTEXT, level VARCHAR(30), created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $charset;";

    foreach ($sql as $statement) { dbDelta($statement); }
});
