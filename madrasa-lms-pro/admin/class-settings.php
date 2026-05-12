<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class MLP_Settings {
	public static function render(): void {
		if ( isset( $_POST['mlp_nonce'] ) ) { MLP_Security::verify_request(); update_option( 'mlp_settings', array( 'institute_name' => MLP_Security::clean_text( $_POST['institute_name'] ?? '' ), 'primary_color' => sanitize_hex_color( $_POST['primary_color'] ?? '#198754' ), 'pwa_enabled' => absint( $_POST['pwa_enabled'] ?? 0 ), 'whatsapp' => MLP_Security::clean_text( $_POST['whatsapp'] ?? '' ), 'email_from' => sanitize_email( $_POST['email_from'] ?? '' ) ) ); }
		mlp_render_view( 'settings', array( 'settings' => get_option( 'mlp_settings', array() ) ) );
	}
}
