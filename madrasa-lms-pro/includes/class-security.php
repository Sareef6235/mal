<?php
/** Security helpers. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MLP_Security {
	public const NONCE_ACTION = 'mlp_admin_action';

	public static function nonce_field(): void { wp_nonce_field( self::NONCE_ACTION, 'mlp_nonce' ); }
	public static function verify_request(): void {
		if ( ! current_user_can( 'manage_mlp' ) && ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'madrasa-lms-pro' ) ); }
		check_admin_referer( self::NONCE_ACTION, 'mlp_nonce' );
	}
	public static function verify_ajax(): void {
		if ( ! current_user_can( 'manage_mlp' ) && ! current_user_can( 'manage_options' ) && ! current_user_can( 'mlp_teacher' ) ) { wp_send_json_error( array( 'message' => __( 'Permission denied.', 'madrasa-lms-pro' ) ), 403 ); }
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
	}
	public static function clean_text( $value ): string { return sanitize_text_field( wp_unslash( (string) $value ) ); }
	public static function clean_textarea( $value ): string { return sanitize_textarea_field( wp_unslash( (string) $value ) ); }
	public static function allowed_upload_mimes( array $mimes ): array {
		$mimes['pdf']  = 'application/pdf';
		$mimes['mp4']  = 'video/mp4';
		$mimes['webm'] = 'video/webm';
		return $mimes;
	}
}
