<?php
if (!defined('ABSPATH')) { exit; }

add_action('admin_menu', function () {
  add_menu_page('MHM Quran Academy', 'MHM Quran Academy', 'manage_options', 'mhm-quran', 'mhm_quran_dashboard', 'dashicons-welcome-learn-more', 3);
  add_submenu_page('mhm-quran', 'Surahs', 'Surah Management', 'manage_options', 'mhm-surahs', 'mhm_surah_manager');
  add_submenu_page('mhm-quran', 'Audio', 'Audio Upload', 'manage_options', 'mhm-audio', 'mhm_audio_manager');
  add_submenu_page('mhm-quran', 'Tajweed', 'Tajweed Editor', 'manage_options', 'mhm-tajweed', 'mhm_tajweed_manager');
  add_submenu_page('mhm-quran', 'Analytics', 'Student Analytics', 'manage_options', 'mhm-analytics', 'mhm_analytics');
});

function mhm_quran_dashboard(){ echo '<div class="wrap"><h1>Premium Quran Platform Dashboard</h1><p>Progress tracking, quizzes, users, and theme controls.</p></div>'; }
function mhm_surah_manager(){ echo '<div class="wrap"><h2>Surah Management</h2></div>'; }
function mhm_audio_manager(){ echo '<div class="wrap"><h2>Audio Upload</h2></div>'; }
function mhm_tajweed_manager(){ echo '<div class="wrap"><h2>Tajweed Rule Editor</h2></div>'; }
function mhm_analytics(){ echo '<div class="wrap"><h2>Student Analytics</h2></div>'; }
