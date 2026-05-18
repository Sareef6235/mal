<?php
/**
 * Reusable modal system.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$modals = array(
	'confirm' => array( __( 'Confirm Submit', 'uesp-theme' ), __( 'Are you sure you want to submit this exam? You can review flagged questions before final submission.', 'uesp-theme' ), '✅' ),
	'warning' => array( __( 'Proctoring Warning', 'uesp-theme' ), __( 'Anti-cheat monitoring is active. Tab switches and fullscreen exits may be logged.', 'uesp-theme' ), '⚠️' ),
	'success' => array( __( 'Success', 'uesp-theme' ), __( 'Your action was completed successfully.', 'uesp-theme' ), '🎉' ),
	'error'   => array( __( 'Error', 'uesp-theme' ), __( 'Something went wrong. Please try again.', 'uesp-theme' ), '⛔' ),
	'expired' => array( __( 'Session Expired', 'uesp-theme' ), __( 'Your secure session has expired. Refresh and sign in again.', 'uesp-theme' ), '🔒' ),
	'delete'  => array( __( 'Delete Confirmation', 'uesp-theme' ), __( 'This destructive action cannot be undone.', 'uesp-theme' ), '🗑️' ),
);
foreach ( $modals as $id => $modal ) :
	?>
	<div class="uesp-modal-overlay" data-uesp-modal-id="<?php echo esc_attr( $id ); ?>" role="dialog" aria-modal="true" aria-labelledby="uesp-modal-title-<?php echo esc_attr( $id ); ?>">
		<div class="uesp-modal uesp-glass">
			<h2 id="uesp-modal-title-<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $modal[2] . ' ' . $modal[0] ); ?></h2>
			<p><?php echo esc_html( $modal[1] ); ?></p>
			<div class="uesp-hero-actions"><button class="uesp-btn" type="button" data-uesp-close-modal><?php esc_html_e( 'Continue', 'uesp-theme' ); ?></button><button class="uesp-btn uesp-btn--ghost" type="button" data-uesp-close-modal><?php esc_html_e( 'Cancel', 'uesp-theme' ); ?></button></div>
		</div>
	</div>
<?php endforeach; ?>
