<?php
/**
 * Widget areas.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function uesp_theme_widgets_init() {
	$sidebars = array(
		'sidebar-1' => __( 'Primary Sidebar', 'uesp-theme' ),
		'dashboard' => __( 'Dashboard Widgets', 'uesp-theme' ),
		'footer-1'  => __( 'Footer Column 1', 'uesp-theme' ),
		'footer-2'  => __( 'Footer Column 2', 'uesp-theme' ),
		'footer-3'  => __( 'Footer Column 3', 'uesp-theme' ),
	);

	foreach ( $sidebars as $id => $name ) {
		register_sidebar(
			array(
				'name'          => esc_html( $name ),
				'id'            => sanitize_key( $id ),
				'description'   => esc_html__( 'Add enterprise LMS widgets here.', 'uesp-theme' ),
				'before_widget' => '<section id="%1$s" class="widget %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h2 class="widget-title">',
				'after_title'   => '</h2>',
			)
		);
	}
}
add_action( 'widgets_init', 'uesp_theme_widgets_init' );
