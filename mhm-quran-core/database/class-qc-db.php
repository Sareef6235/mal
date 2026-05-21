<?php
if (!defined('ABSPATH')) { exit; }
class QC_DB {
  public static function install(): void {
    global $wpdb; require_once ABSPATH.'wp-admin/includes/upgrade.php'; $c=$wpdb->get_charset_collate(); $p=$wpdb->prefix;
    $sql=[];
    $sql[]="CREATE TABLE {$p}quran_surahs (id SMALLINT UNSIGNED PRIMARY KEY,name_ar VARCHAR(120) NOT NULL,name_en VARCHAR(120) NOT NULL,total_ayahs SMALLINT UNSIGNED NOT NULL,revelation_type VARCHAR(20) NOT NULL,KEY name_en(name_en)) $c;";
    $sql[]="CREATE TABLE {$p}quran_ayahs (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,surah_id SMALLINT UNSIGNED NOT NULL,ayah_number SMALLINT UNSIGNED NOT NULL,arabic_text LONGTEXT NOT NULL,UNIQUE KEY surah_ayah(surah_id,ayah_number),KEY surah_idx(surah_id),CONSTRAINT fk_qc_ayah_surah FOREIGN KEY (surah_id) REFERENCES {$p}quran_surahs(id) ON DELETE CASCADE) $c;";
    $sql[]="CREATE TABLE {$p}quran_words (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,ayah_id BIGINT UNSIGNED NOT NULL,word_index SMALLINT UNSIGNED NOT NULL,word_ar VARCHAR(255),word_translation VARCHAR(255),start_ms INT UNSIGNED,end_ms INT UNSIGNED,KEY ayah_word(ayah_id,word_index),CONSTRAINT fk_qc_words_ayah FOREIGN KEY (ayah_id) REFERENCES {$p}quran_ayahs(id) ON DELETE CASCADE) $c;";
    $sql[]="CREATE TABLE {$p}quran_reciters (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,slug VARCHAR(80) UNIQUE,name VARCHAR(120) NOT NULL,base_url TEXT,language VARCHAR(16),bitrate SMALLINT) $c;";
    $sql[]="CREATE TABLE {$p}quran_audio (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,ayah_id BIGINT UNSIGNED NOT NULL,reciter_id BIGINT UNSIGNED NOT NULL,audio_url TEXT NOT NULL,duration FLOAT,waveform JSON,KEY ayah_reciter(ayah_id,reciter_id),CONSTRAINT fk_qc_audio_ayah FOREIGN KEY (ayah_id) REFERENCES {$p}quran_ayahs(id) ON DELETE CASCADE,CONSTRAINT fk_qc_audio_rec FOREIGN KEY (reciter_id) REFERENCES {$p}quran_reciters(id) ON DELETE CASCADE) $c;";
    $sql[]="CREATE TABLE {$p}quran_tajweed (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,ayah_id BIGINT UNSIGNED NOT NULL,rule_type VARCHAR(40),start_pos SMALLINT UNSIGNED,end_pos SMALLINT UNSIGNED,tooltip TEXT,KEY ayah_rule(ayah_id,rule_type),CONSTRAINT fk_qc_taj_ayah FOREIGN KEY (ayah_id) REFERENCES {$p}quran_ayahs(id) ON DELETE CASCADE) $c;";
    $sql[]="CREATE TABLE {$p}quran_tafsir (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,ayah_id BIGINT UNSIGNED NOT NULL,source VARCHAR(120),lang VARCHAR(10),tafsir_text LONGTEXT,KEY ayah_lang(ayah_id,lang),CONSTRAINT fk_qc_taf_ayah FOREIGN KEY (ayah_id) REFERENCES {$p}quran_ayahs(id) ON DELETE CASCADE) $c;";
    $sql[]="CREATE TABLE {$p}quran_bookmarks (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED NOT NULL,ayah_id BIGINT UNSIGNED NOT NULL,note VARCHAR(255),created_at DATETIME DEFAULT CURRENT_TIMESTAMP,KEY user_ayah(user_id,ayah_id),CONSTRAINT fk_qc_bm_ayah FOREIGN KEY (ayah_id) REFERENCES {$p}quran_ayahs(id) ON DELETE CASCADE) $c;";
    $sql[]="CREATE TABLE {$p}quran_progress (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED NOT NULL,surah_id SMALLINT UNSIGNED NOT NULL,ayah_number SMALLINT UNSIGNED NOT NULL,completion_pct DECIMAL(5,2) DEFAULT 0,updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,KEY user_idx(user_id),CONSTRAINT fk_qc_prog_surah FOREIGN KEY (surah_id) REFERENCES {$p}quran_surahs(id) ON DELETE CASCADE) $c;";
    $sql[]="CREATE TABLE {$p}quran_notes (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED NOT NULL,ayah_id BIGINT UNSIGNED NOT NULL,note LONGTEXT,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,KEY user_ayah(user_id,ayah_id),CONSTRAINT fk_qc_note_ayah FOREIGN KEY (ayah_id) REFERENCES {$p}quran_ayahs(id) ON DELETE CASCADE) $c;";
    $sql[]="CREATE TABLE {$p}quran_quizzes (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,title VARCHAR(200),quiz_json LONGTEXT,level VARCHAR(20),created_at DATETIME DEFAULT CURRENT_TIMESTAMP) $c;";
    foreach($sql as $q){dbDelta($q);} update_option('mhm_qc_db_version','1.0.0');
  }
}
