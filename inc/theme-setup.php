<?php
/**
 * Theme setup.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function uesp_theme_setup() {
	load_theme_textdomain( 'uesp-theme', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 240, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'menus' );
	add_theme_support( 'widgets' );
	add_theme_support( 'woocommerce' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_editor_style( 'assets/css/editor-style.css' );

	register_nav_menus(
		array(
			'primary' => esc_html__( 'Primary Menu', 'uesp-theme' ),
			'footer'  => esc_html__( 'Footer Menu', 'uesp-theme' ),
		)
	);
}
add_action( 'after_setup_theme', 'uesp_theme_setup' );

function uesp_theme_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'uesp_theme_content_width', 1180 );
}
add_action( 'after_setup_theme', 'uesp_theme_content_width', 0 );
