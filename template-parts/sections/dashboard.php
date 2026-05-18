<?php
/**
 * Dashboard section.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="uesp-section">
	<div class="uesp-container">
		<h2 class="uesp-section-title"><?php esc_html_e( 'Student command center', 'uesp-theme' ); ?></h2>
		<p class="uesp-section-lead"><?php esc_html_e( 'Welcome banner, stats, exam progress, performance charts, recent exams, notifications, ranks, and upcoming schedules in a responsive dashboard.', 'uesp-theme' ); ?></p>
		<div class="uesp-grid uesp-grid-4">
			<?php
			$stats = array(
				array( '📘', __( 'Books Completed', 'uesp-theme' ), '42' ),
				array( '📝', __( 'Mock Exams', 'uesp-theme' ), '18' ),
				array( '🎯', __( 'Accuracy', 'uesp-theme' ), '86%' ),
				array( '🔥', __( 'Study Streak', 'uesp-theme' ), '21d' ),
			);
			foreach ( $stats as $stat ) :
				?>
				<div class="uesp-card uesp-stat" data-uesp-animate><span class="uesp-stat-icon"><?php echo esc_html( $stat[0] ); ?></span><div><strong><?php echo esc_html( $stat[2] ); ?></strong><span><?php echo esc_html( $stat[1] ); ?></span></div></div>
			<?php endforeach; ?>
		</div>
		<div class="uesp-grid uesp-grid-3" style="margin-top:24px">
			<div class="uesp-card"><h3><?php esc_html_e( 'Exam Progress', 'uesp-theme' ); ?></h3><p><?php esc_html_e( 'Physics Final', 'uesp-theme' ); ?></p><div class="uesp-progress"><span style="width:72%"></span></div></div>
			<div class="uesp-card"><h3><?php esc_html_e( 'Upcoming Exams', 'uesp-theme' ); ?></h3><p><?php esc_html_e( 'Math Advanced • Tomorrow • 10:00 UTC', 'uesp-theme' ); ?></p><p><?php esc_html_e( 'Biology Mock • Friday • 14:30 UTC', 'uesp-theme' ); ?></p></div>
			<div class="uesp-card"><h3><?php esc_html_e( 'Notifications', 'uesp-theme' ); ?></h3><p><?php esc_html_e( 'Certificate ready. New leaderboard position unlocked. Membership renewal in 7 days.', 'uesp-theme' ); ?></p></div>
		</div>
		<div class="uesp-card" style="margin-top:24px">
			<h3><?php esc_html_e( 'Text-Based Import Guard', 'uesp-theme' ); ?></h3>
			<p><?php esc_html_e( 'Upload only PHP, JS, CSS, HTML, JSON, or TXT files for processing. Binary files are rejected on both client and server.', 'uesp-theme' ); ?></p>
			<form class="uesp-form" enctype="multipart/form-data" data-uesp-text-upload>
				<label class="uesp-field"><span><?php esc_html_e( 'Choose a text file', 'uesp-theme' ); ?></span><input type="file" name="file" accept=".php,.js,.css,.html,.json,.txt,text/plain,text/css,text/html,application/json,application/javascript"></label>
				<button class="uesp-btn" type="submit"><?php esc_html_e( 'Validate Upload', 'uesp-theme' ); ?></button>
			</form>
		</div>
	</div>
</section>
