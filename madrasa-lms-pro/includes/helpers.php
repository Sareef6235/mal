<?php
/** Helper functions. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function mlp_table( string $name ): string {
	global $wpdb;
	return $wpdb->prefix . 'mlp_' . preg_replace( '/[^a-z0-9_]/', '', $name );
}

function mlp_current_user_can_manage(): bool {
	return current_user_can( 'manage_mlp' ) || current_user_can( 'manage_options' );
}

function mlp_render_view( string $view, array $data = array(), bool $public = false ): void {
	$base = $public ? MLP_PATH . 'public/views/' : MLP_PATH . 'admin/views/';
	$file = $base . basename( $view ) . '.php';
	if ( file_exists( $file ) ) {
		extract( $data, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		include $file;
	}
}

function mlp_json_success( array $data = array() ): void {
	wp_send_json_success( $data );
}
