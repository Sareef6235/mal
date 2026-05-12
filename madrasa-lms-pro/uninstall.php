<?php
/** Uninstall cleanup. */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }
global $wpdb;
$tables = array( 'students', 'teachers', 'courses', 'lessons', 'attendance', 'exams', 'results', 'certificates', 'monthly_plans', 'subjects', 'notifications' );
foreach ( $tables as $table ) { $wpdb->query( 'DROP TABLE IF EXISTS ' . esc_sql( $wpdb->prefix . 'mlp_' . $table ) ); }
delete_option( 'mlp_settings' );
remove_role( 'mlp_admin' ); remove_role( 'mlp_teacher' ); remove_role( 'mlp_student' );
