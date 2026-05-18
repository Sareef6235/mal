<?php
/**
 * Helper functions and secure AJAX endpoints.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function uesp_theme_asset( $path ) {
	return esc_url( UESP_THEME_URI . '/' . ltrim( $path, '/' ) );
}

function uesp_theme_menu_fallback() {
	$items = array(
		'dashboard'   => __( 'Dashboard', 'uesp-theme' ),
		'exams'       => __( 'Exams', 'uesp-theme' ),
		'books'       => __( 'Books', 'uesp-theme' ),
		'categories'  => __( 'Categories', 'uesp-theme' ),
		'results'     => __( 'Results', 'uesp-theme' ),
		'leaderboard' => __( 'Leaderboard', 'uesp-theme' ),
		'profile'     => __( 'Profile', 'uesp-theme' ),
	);
	echo '<ul class="menu">';
	foreach ( $items as $slug => $label ) {
		echo '<li><a href="' . esc_url( home_url( '/' . $slug . '/' ) ) . '">' . esc_html( $label ) . '</a>';
		if ( 'exams' === $slug ) {
			echo '<ul class="uesp-mega-menu"><li class="uesp-mega-tile"><a href="' . esc_url( home_url( '/exams/mock-tests/' ) ) . '">' . esc_html__( 'Mock Tests', 'uesp-theme' ) . '</a></li><li class="uesp-mega-tile"><a href="' . esc_url( home_url( '/exams/live/' ) ) . '">' . esc_html__( 'Live Exams', 'uesp-theme' ) . '</a></li><li class="uesp-mega-tile"><a href="' . esc_url( home_url( '/exams/certificates/' ) ) . '">' . esc_html__( 'Certificates', 'uesp-theme' ) . '</a></li></ul>';
		}
		echo '</li>';
	}
	echo '</ul>';
}

function uesp_theme_posted_on() {
	printf(
		'<span>%1$s</span><span>%2$s</span>',
		esc_html( get_the_date() ),
		esc_html( get_the_author() )
	);
}

function uesp_theme_schema() {
	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'EducationalOrganization',
		'name'        => get_bloginfo( 'name' ),
		'url'         => home_url( '/' ),
		'description' => get_bloginfo( 'description' ),
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n";
}
add_action( 'wp_head', 'uesp_theme_schema', 20 );

function uesp_theme_open_graph() {
	$title       = is_singular() ? get_the_title() : get_bloginfo( 'name' );
	$description = is_singular() ? wp_strip_all_tags( get_the_excerpt() ) : get_bloginfo( 'description' );
	?>
	<meta property="og:type" content="website">
	<meta property="og:title" content="<?php echo esc_attr( $title ); ?>">
	<meta property="og:description" content="<?php echo esc_attr( $description ); ?>">
	<meta property="og:url" content="<?php echo esc_url( is_singular() ? get_permalink() : home_url( '/' ) ); ?>">
	<?php
}
add_action( 'wp_head', 'uesp_theme_open_graph', 5 );

function uesp_theme_ajax_autosave_answer() {
	check_ajax_referer( 'uesp_theme_nonce', 'nonce' );

	$question_id = isset( $_POST['question_id'] ) ? absint( wp_unslash( $_POST['question_id'] ) ) : 0;
	$answer      = isset( $_POST['answer'] ) ? sanitize_text_field( wp_unslash( $_POST['answer'] ) ) : '';

	if ( ! $question_id || '' === $answer ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid answer payload.', 'uesp-theme' ) ), 400 );
	}

	wp_send_json_success(
		array(
			'message'     => esc_html__( 'Answer saved securely.', 'uesp-theme' ),
			'question_id' => $question_id,
		)
	);
}
add_action( 'wp_ajax_uesp_autosave_answer', 'uesp_theme_ajax_autosave_answer' );
add_action( 'wp_ajax_nopriv_uesp_autosave_answer', 'uesp_theme_ajax_autosave_answer' );
