<?php
/**
 * Customizer options.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function uesp_theme_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'uesp_theme_home',
		array(
			'title'    => esc_html__( 'UESP Hero', 'uesp-theme' ),
			'priority' => 30,
		)
	);

	$wp_customize->add_setting(
		'uesp_hero_title',
		array(
			'default'           => esc_html__( 'Ultimate Exam & Book Quiz System Pro', 'uesp-theme' ),
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'uesp_hero_title',
		array(
			'label'   => esc_html__( 'Hero title', 'uesp-theme' ),
			'section' => 'uesp_theme_home',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'uesp_hero_text',
		array(
			'default'           => esc_html__( 'A premium SaaS LMS theme for exams, quizzes, books, analytics, memberships, certificates, and enterprise learning workflows.', 'uesp-theme' ),
			'sanitize_callback' => 'sanitize_textarea_field',
		)
	);
	$wp_customize->add_control(
		'uesp_hero_text',
		array(
			'label'   => esc_html__( 'Hero description', 'uesp-theme' ),
			'section' => 'uesp_theme_home',
			'type'    => 'textarea',
		)
	);
}
add_action( 'customize_register', 'uesp_theme_customize_register' );
