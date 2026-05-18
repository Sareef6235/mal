<?php
/**
 * Live exam UI section.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="uesp-section">
	<div class="uesp-container">
		<h2 class="uesp-section-title"><?php esc_html_e( 'Secure live exam interface', 'uesp-theme' ); ?></h2>
		<div class="uesp-exam-shell">
			<aside class="uesp-card uesp-question-palette">
				<div class="uesp-timer" data-uesp-timer="1800">⏱ <span>30:00</span></div>
				<h3><?php esc_html_e( 'Question Palette', 'uesp-theme' ); ?></h3>
				<div class="uesp-palette-grid">
					<?php for ( $i = 1; $i <= 20; $i++ ) : ?>
						<button class="<?php echo $i < 6 ? 'is-answered' : ''; ?>" type="button"><?php echo esc_html( (string) $i ); ?></button>
					<?php endfor; ?>
				</div>
			</aside>
			<div class="uesp-card">
				<div class="uesp-meta"><span><?php esc_html_e( 'Auto-save enabled', 'uesp-theme' ); ?></span><span><?php esc_html_e( 'Fullscreen proctoring ready', 'uesp-theme' ); ?></span><span><?php esc_html_e( 'Tab switch detection active', 'uesp-theme' ); ?></span></div>
				<h3><?php esc_html_e( 'Which learning analytics signal best predicts exam readiness?', 'uesp-theme' ); ?></h3>
				<form class="uesp-answer-list" data-uesp-autosave>
					<?php foreach ( array( 'Total login count', 'Weighted mastery trend', 'Profile avatar color', 'Random forum visits' ) as $index => $answer ) : ?>
					<label class="uesp-answer"><input type="radio" name="answer" value="<?php echo esc_attr( (string) $index ); ?>" data-question-id="101"><span><?php echo esc_html( $answer ); ?></span></label>
					<?php endforeach; ?>
				</form>
				<div class="uesp-hero-actions"><button class="uesp-btn uesp-btn--ghost" type="button"><?php esc_html_e( 'Previous', 'uesp-theme' ); ?></button><button class="uesp-btn" type="button"><?php esc_html_e( 'Next', 'uesp-theme' ); ?></button><button class="uesp-btn" data-uesp-modal="confirm" type="button"><?php esc_html_e( 'Submit Exam', 'uesp-theme' ); ?></button></div>
			</div>
		</div>
	</div>
</section>
