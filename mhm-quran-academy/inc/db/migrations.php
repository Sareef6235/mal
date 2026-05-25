<?php
if (!defined('ABSPATH')) { exit; }

function mhm_quran_run_migrations(): void {
    global $wpdb;
    $version = get_option('mhm_quran_db_version', '0');
    if (version_compare($version, '1.1.0', '>=')) { return; }

    // add indexes and reciters table migration
    $wpdb->query("ALTER TABLE {$wpdb->prefix}quran_ayahs ADD FULLTEXT KEY arabic_text_ft (arabic_text)");
    $wpdb->query("ALTER TABLE {$wpdb->prefix}quran_translations ADD FULLTEXT KEY translation_ft (translation)");
    $wpdb->query("ALTER TABLE {$wpdb->prefix}quran_audio ADD KEY reciter_idx (reciter)");
    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}quran_reciters (
      id BIGINT PRIMARY KEY AUTO_INCREMENT,
      slug VARCHAR(80) UNIQUE,
      name VARCHAR(120) NOT NULL,
      language VARCHAR(20) DEFAULT 'ar',
      bitrate SMALLINT DEFAULT 128,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) {$wpdb->get_charset_collate()}");

    update_option('mhm_quran_db_version', '1.1.0');
}
add_action('init', 'mhm_quran_run_migrations');
