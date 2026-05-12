<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class MLP_Attendance {
	public static function render(): void { mlp_render_view( 'attendance', array( 'rows' => MLP_DB::rows( 'attendance' ), 'students' => MLP_DB::rows( 'students', 200 ) ) ); }
	public static function ajax_save(): void { MLP_Security::verify_ajax(); global $wpdb; $student_id = absint( $_POST['student_id'] ?? 0 ); $date = MLP_Security::clean_text( $_POST['attendance_date'] ?? gmdate( 'Y-m-d' ) ); $status = MLP_Security::clean_text( $_POST['status'] ?? 'present' ); $wpdb->query( $wpdb->prepare( 'INSERT INTO ' . esc_sql( mlp_table( 'attendance' ) ) . ' (student_id,attendance_date,status,created_by,created_at) VALUES (%d,%s,%s,%d,%s) ON DUPLICATE KEY UPDATE status=%s', $student_id, $date, $status, get_current_user_id(), current_time( 'mysql' ), $status ) ); mlp_json_success( array( 'saved' => true ) ); }
}
