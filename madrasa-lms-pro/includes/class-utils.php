<?php
/** Utility service. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MLP_Utils {
	public static function grade( float $marks, float $total ): string {
		$percent = $total > 0 ? ( $marks / $total ) * 100 : 0;
		if ( $percent >= 90 ) { return 'A+'; }
		if ( $percent >= 80 ) { return 'A'; }
		if ( $percent >= 70 ) { return 'B'; }
		if ( $percent >= 60 ) { return 'C'; }
		if ( $percent >= 50 ) { return 'D'; }
		return 'F';
	}
	public static function upload_url( int $attachment_id ): string { return esc_url( wp_get_attachment_url( $attachment_id ) ?: '' ); }
	public static function date( string $date ): string { return esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) ); }
}
