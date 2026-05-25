<?php
if (!defined('WP_UNINSTALL_PLUGIN')) { exit; }
global $wpdb;
$tables=['quran_surahs','quran_ayahs','quran_words','quran_audio','quran_tajweed','quran_tafsir','quran_bookmarks','quran_progress','quran_notes','quran_quizzes','quran_reciters'];
foreach($tables as $t){$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$t}");}
delete_option('mhm_qc_db_version');
