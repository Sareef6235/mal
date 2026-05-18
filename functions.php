<?php
/**
 * Ultimate Exam System Pro Theme functions.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'UESP_THEME_VERSION', '1.0.0' );
define( 'UESP_THEME_DIR', get_template_directory() );
define( 'UESP_THEME_URI', get_template_directory_uri() );

require_once UESP_THEME_DIR . '/inc/helpers.php';
require_once UESP_THEME_DIR . '/inc/file-validation.php';
require_once UESP_THEME_DIR . '/inc/theme-setup.php';
require_once UESP_THEME_DIR . '/inc/enqueue.php';
require_once UESP_THEME_DIR . '/inc/widgets.php';
require_once UESP_THEME_DIR . '/inc/customizer.php';
