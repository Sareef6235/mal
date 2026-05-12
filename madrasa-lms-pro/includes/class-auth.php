<?php
/** Authentication hardening. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MLP_Auth {
	public function hooks(): void { add_filter( 'authenticate', array( $this, 'rate_limit_login' ), 30, 3 ); }
	public function rate_limit_login( $user, string $username, string $password ) {
		if ( empty( $username ) || $user instanceof WP_User ) { return $user; }
		$key = 'mlp_login_' . md5( strtolower( $username ) . '|' . ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$attempts = (int) get_transient( $key );
		if ( $attempts >= 5 ) { return new WP_Error( 'mlp_rate_limited', __( 'Too many login attempts. Please try again later.', 'madrasa-lms-pro' ) ); }
		if ( is_wp_error( $user ) ) { set_transient( $key, $attempts + 1, 15 * MINUTE_IN_SECONDS ); }
		return $user;
	}
}
