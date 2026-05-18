<?php
/**
 * Header template.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="uesp-preloader" aria-hidden="true"><div class="uesp-loader"></div></div>
<a class="screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'uesp-theme' ); ?></a>
<header class="uesp-site-header">
	<div class="uesp-container uesp-header-inner">
		<a class="uesp-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<span class="uesp-brand-mark" aria-hidden="true">UE</span>
				<span><?php echo esc_html( get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : __( 'Exam Pro', 'uesp-theme' ) ); ?></span>
			<?php endif; ?>
		</a>

		<nav class="uesp-nav" aria-label="<?php esc_attr_e( 'Primary menu', 'uesp-theme' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'fallback_cb'    => 'uesp_theme_menu_fallback',
				)
			);
			?>
		</nav>

		<div class="uesp-actions">
			<form class="uesp-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="screen-reader-text" for="uesp-header-search"><?php esc_html_e( 'Search', 'uesp-theme' ); ?></label>
				<span aria-hidden="true">⌕</span>
				<input id="uesp-header-search" name="s" type="search" placeholder="<?php esc_attr_e( 'Search exams...', 'uesp-theme' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>">
			</form>
			<button class="uesp-icon-btn uesp-dark-toggle" type="button" aria-label="<?php esc_attr_e( 'Toggle dark mode', 'uesp-theme' ); ?>">☾</button>
			<button class="uesp-icon-btn uesp-notification" type="button" data-uesp-modal="warning" aria-label="<?php esc_attr_e( 'Notifications', 'uesp-theme' ); ?>">🔔</button>
			<div class="uesp-profile">
				<button class="uesp-avatar" type="button" aria-label="<?php esc_attr_e( 'Profile menu', 'uesp-theme' ); ?>">A</button>
				<div class="uesp-profile-menu">
					<a href="<?php echo esc_url( home_url( '/profile/' ) ); ?>"><?php esc_html_e( 'My Profile', 'uesp-theme' ); ?></a>
					<a href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>"><?php esc_html_e( 'Dashboard', 'uesp-theme' ); ?></a>
					<a href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'Login', 'uesp-theme' ); ?></a>
				</div>
			</div>
			<button class="uesp-icon-btn uesp-menu-toggle" type="button" aria-label="<?php esc_attr_e( 'Open menu', 'uesp-theme' ); ?>">☰</button>
		</div>
	</div>
</header>
<div class="uesp-mobile-drawer" aria-hidden="true">
	<button class="uesp-icon-btn uesp-menu-close" type="button" aria-label="<?php esc_attr_e( 'Close menu', 'uesp-theme' ); ?>">×</button>
	<?php uesp_theme_menu_fallback(); ?>
</div>
<nav class="uesp-bottom-nav" aria-label="<?php esc_attr_e( 'Mobile bottom navigation', 'uesp-theme' ); ?>">
	<a href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>">🏠</a>
	<a href="<?php echo esc_url( home_url( '/exams/' ) ); ?>">📝</a>
	<a href="<?php echo esc_url( home_url( '/books/' ) ); ?>">📚</a>
	<a href="<?php echo esc_url( home_url( '/results/' ) ); ?>">📊</a>
	<a href="<?php echo esc_url( home_url( '/profile/' ) ); ?>">👤</a>
</nav>
<main id="primary" class="uesp-main">
