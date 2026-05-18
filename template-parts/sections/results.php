<?php
/**
 * Result section.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="uesp-section">
	<div class="uesp-container">
		<div class="uesp-grid uesp-grid-3">
			<div class="uesp-card" data-uesp-animate><h2><?php esc_html_e( 'Result Summary', 'uesp-theme' ); ?></h2><div class="uesp-score-circle"><span>86%</span></div><p class="uesp-badge uesp-rank-gold">🏆 <?php esc_html_e( 'Gold Rank', 'uesp-theme' ); ?></p></div>
			<div class="uesp-card" data-uesp-animate><h3><?php esc_html_e( 'Accuracy & Time', 'uesp-theme' ); ?></h3><p><?php esc_html_e( 'Correct: 86 • Wrong: 14 • Skipped: 0', 'uesp-theme' ); ?></p><p><?php esc_html_e( 'Average time per question: 42 seconds. Fastest topic: Algebra.', 'uesp-theme' ); ?></p><a class="uesp-btn" href="#"><?php esc_html_e( 'Download Certificate', 'uesp-theme' ); ?></a></div>
			<div class="uesp-card" data-uesp-animate><h3><?php esc_html_e( 'Leaderboard Comparison', 'uesp-theme' ); ?></h3><div class="uesp-table-wrap"><table><tbody><tr><td data-label="Rank">#11</td><td data-label="Student">Maya</td><td data-label="Score">88%</td></tr><tr><td data-label="Rank">#12</td><td data-label="Student"><?php esc_html_e( 'You', 'uesp-theme' ); ?></td><td data-label="Score">86%</td></tr><tr><td data-label="Rank">#13</td><td data-label="Student">Noah</td><td data-label="Score">85%</td></tr></tbody></table></div></div>
		</div>
	</div>
</section>
