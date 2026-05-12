<?php
/** Certificate generation. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MLP_Certificates {
	public static function create( int $student_id, int $course_id = 0 ): string {
		global $wpdb;
		$number = 'MLP-' . gmdate( 'Ymd' ) . '-' . wp_generate_password( 6, false, false );
		$hash   = wp_hash( $number . '|' . $student_id );
		$wpdb->query( $wpdb->prepare( 'INSERT INTO ' . esc_sql( mlp_table( 'certificates' ) ) . ' (student_id,course_id,certificate_no,verification_hash,issued_at) VALUES (%d,%d,%s,%s,%s)', $student_id, $course_id, $number, $hash, current_time( 'mysql' ) ) );
		return $number;
	}
	public static function html( string $certificate_no ): string {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT c.*,s.name FROM ' . esc_sql( mlp_table( 'certificates' ) ) . ' c LEFT JOIN ' . esc_sql( mlp_table( 'students' ) ) . ' s ON s.id=c.student_id WHERE c.certificate_no=%s', $certificate_no ), ARRAY_A );
		if ( ! $row ) { return esc_html__( 'Certificate not found.', 'madrasa-lms-pro' ); }
		$verify = esc_url( add_query_arg( array( 'mlp_verify' => $row['verification_hash'] ), home_url( '/' ) ) );
		return '<div class="mlp-certificate"><h1>' . esc_html__( 'Certificate of Completion', 'madrasa-lms-pro' ) . '</h1><h2>' . esc_html( $row['name'] ) . '</h2><p>' . esc_html( $row['certificate_no'] ) . '</p><p>' . esc_url( $verify ) . '</p></div>';
	}
}
