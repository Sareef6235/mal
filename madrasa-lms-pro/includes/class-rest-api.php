<?php
/** REST API endpoints. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MLP_Rest_API {
	public function hooks(): void { add_action( 'rest_api_init', array( $this, 'register_routes' ) ); }
	public function register_routes(): void {
		register_rest_route( 'mlp/v1', '/login', array( 'methods' => 'POST', 'callback' => array( $this, 'login' ), 'permission_callback' => '__return_true' ) );
		foreach ( array( 'courses', 'students', 'attendance', 'results', 'monthly-plans' ) as $route ) {
			register_rest_route( 'mlp/v1', '/' . $route, array(
				array( 'methods' => 'GET', 'callback' => array( $this, 'list' ), 'permission_callback' => array( $this, 'can_read' ), 'args' => array( 'resource' => array( 'default' => $route ) ) ),
				array( 'methods' => 'POST', 'callback' => array( $this, 'create' ), 'permission_callback' => array( $this, 'can_write' ), 'args' => array( 'resource' => array( 'default' => $route ) ) ),
			) );
		}
	}
	public function can_read( WP_REST_Request $request ): bool { return is_user_logged_in() && wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ); }
	public function can_write( WP_REST_Request $request ): bool { return $this->can_read( $request ) && ( current_user_can( 'manage_mlp' ) || current_user_can( 'mlp_teacher' ) ); }
	public function login( WP_REST_Request $request ): WP_REST_Response {
		$creds = array( 'user_login' => sanitize_user( (string) $request['username'] ), 'user_password' => (string) $request['password'], 'remember' => true );
		$user  = wp_signon( $creds, is_ssl() );
		if ( is_wp_error( $user ) ) { return new WP_REST_Response( array( 'message' => $user->get_error_message() ), 401 ); }
		return new WP_REST_Response( array( 'token' => wp_create_nonce( 'wp_rest' ), 'user_id' => $user->ID, 'roles' => $user->roles ), 200 );
	}
	public function list( WP_REST_Request $request ): WP_REST_Response { return new WP_REST_Response( MLP_DB::rows( $this->map_resource( (string) $request['resource'] ) ), 200 ); }
	public function create( WP_REST_Request $request ): WP_REST_Response { return new WP_REST_Response( MLP_Admin_Menu::save_resource( $this->map_resource( (string) $request['resource'] ), $request->get_json_params() ?: array() ), 201 ); }
	private function map_resource( string $resource ): string { return str_replace( '-', '_', sanitize_key( $resource ) ); }
}
