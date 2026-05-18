<?php
/**
 * Assets and front-end integration.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function uesp_theme_assets() {
	wp_enqueue_style( 'main-style', get_stylesheet_uri(), array(), UESP_THEME_VERSION );
	wp_enqueue_style( 'uesp-components', UESP_THEME_URI . '/assets/css/components.css', array( 'main-style' ), UESP_THEME_VERSION );

	wp_enqueue_script( 'main-js', UESP_THEME_URI . '/assets/js/main.js', array(), UESP_THEME_VERSION, true );
	wp_localize_script(
		'main-js',
		'uespTheme',
		array(
			'ajaxUrl' => esc_url( admin_url( 'admin-ajax.php' ) ),
			'nonce'   => wp_create_nonce( 'uesp_theme_nonce' ),
			'i18n'    => array(
				'saved'       => esc_html__( 'Answer autosaved', 'uesp-theme' ),
				'tabWarning'  => esc_html__( 'Tab switch detected. Your proctoring warning has been logged.', 'uesp-theme' ),
				'sessionDone' => esc_html__( 'Session expired. Please reconnect to continue.', 'uesp-theme' ),
			),
		)
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'uesp_theme_assets' );

function uesp_theme_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous' );
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'uesp_theme_resource_hints', 10, 2 );
