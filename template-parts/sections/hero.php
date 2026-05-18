<?php
/**
 * Hero section.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="uesp-hero">
	<div class="uesp-container uesp-hero-grid">
		<div data-uesp-animate>
			<div class="uesp-kicker"><?php esc_html_e( 'Modern LMS + Online Exam SaaS', 'uesp-theme' ); ?></div>
			<h1><?php echo esc_html( get_theme_mod( 'uesp_hero_title', __( 'Ultimate Exam & Book Quiz System Pro', 'uesp-theme' ) ) ); ?></h1>
			<p><?php echo esc_html( get_theme_mod( 'uesp_hero_text', __( 'A premium SaaS LMS theme for exams, quizzes, books, analytics, memberships, certificates, and enterprise learning workflows.', 'uesp-theme' ) ) ); ?></p>
			<div class="uesp-hero-actions">
				<a class="uesp-btn" href="<?php echo esc_url( home_url( '/exams/' ) ); ?>"><?php esc_html_e( 'Start Live Exam', 'uesp-theme' ); ?></a>
				<a class="uesp-btn uesp-btn--ghost" href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>"><?php esc_html_e( 'View Dashboard', 'uesp-theme' ); ?></a>
			</div>
		</div>
		<div class="uesp-hero-visual" data-uesp-animate>
			<div class="uesp-dashboard-preview uesp-glass">
				<div class="uesp-preview-row">
					<div class="uesp-mini-card"><strong><?php esc_html_e( 'Accuracy', 'uesp-theme' ); ?></strong><div class="uesp-progress"><span style="width:86%"></span></div></div>
					<div class="uesp-mini-card"><strong><?php esc_html_e( 'Rank', 'uesp-theme' ); ?></strong><p class="uesp-badge">🏆 #12</p></div>
				</div>
				<div class="uesp-card">
					<div class="uesp-meta"><span><?php esc_html_e( 'Performance Chart', 'uesp-theme' ); ?></span><span><?php esc_html_e( 'AI Suggestions', 'uesp-theme' ); ?></span></div>
					<div class="uesp-chart"><span style="height:46%"></span><span style="height:68%"></span><span style="height:54%"></span><span style="height:90%"></span><span style="height:76%"></span><span style="height:88%"></span></div>
				</div>
				<div class="uesp-mini-card uesp-ai-suggestion"><strong><?php esc_html_e( 'Next best action', 'uesp-theme' ); ?></strong><p><?php esc_html_e( 'Review Organic Chemistry flashcards before your scheduled mock exam.', 'uesp-theme' ); ?></p></div>
			</div>
		</div>
	</div>
</section>
