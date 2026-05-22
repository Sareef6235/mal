<?php
global $wpdb; $surah=absint($_GET['surah']??1);
$rows=$wpdb->get_results($wpdb->prepare("SELECT id,ayah_number,arabic_text FROM {$wpdb->prefix}quran_ayahs WHERE surah_id=%d ORDER BY ayah_number",$surah),ARRAY_A);
?><!doctype html><html><body><main class="qc-reader-page"><h1>Quran Reader</h1><?php foreach($rows as $r): ?><article><h4><?php echo esc_html((string)$r['ayah_number']); ?></h4><p dir="rtl"><?php echo esc_html($r['arabic_text']); ?></p><button class="qc-btn js-bookmark" data-ayah="<?php echo esc_attr((string)$r['id']); ?>">Bookmark</button></article><?php endforeach; ?></main></body></html>
