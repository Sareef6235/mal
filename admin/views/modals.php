<?php
/**
 * Admin modal templates.
 *
 * @package MAL_Premium_LMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="mal-lms-modal" id="plan-modal" aria-hidden="true">
	<div class="mal-lms-modal__panel" role="dialog" aria-modal="true">
		<button class="mal-lms-modal__close" data-close-modal type="button">×</button>
		<h2><?php esc_html_e( 'Add Monthly Plan Item', 'mal-premium-lms' ); ?></h2>
		<form data-ajax-form="mal_lms_save_plan">
			<input name="title" required placeholder="<?php esc_attr_e( 'Plan title', 'mal-premium-lms' ); ?>" />
			<input name="class_name" required placeholder="<?php esc_attr_e( 'Class', 'mal-premium-lms' ); ?>" />
			<input name="subject" required placeholder="<?php esc_attr_e( 'Subject', 'mal-premium-lms' ); ?>" />
			<input name="week_number" type="number" min="1" max="6" value="1" />
			<textarea name="description" placeholder="<?php esc_attr_e( 'Learning outcomes', 'mal-premium-lms' ); ?>"></textarea>
			<button class="mal-lms-primary" type="submit"><?php esc_html_e( 'Save Plan', 'mal-premium-lms' ); ?></button>
		</form>
	</div>
</div>
<div class="mal-lms-modal" id="course-modal" aria-hidden="true">
	<div class="mal-lms-modal__panel" role="dialog" aria-modal="true">
		<button class="mal-lms-modal__close" data-close-modal type="button">×</button>
		<h2><?php esc_html_e( 'Add Course', 'mal-premium-lms' ); ?></h2>
		<form data-ajax-form="mal_lms_save_course">
			<input name="title" required placeholder="<?php esc_attr_e( 'Course title', 'mal-premium-lms' ); ?>" />
			<input name="class_name" placeholder="<?php esc_attr_e( 'Class', 'mal-premium-lms' ); ?>" />
			<input name="subject" placeholder="<?php esc_attr_e( 'Subject', 'mal-premium-lms' ); ?>" />
			<textarea name="description" placeholder="<?php esc_attr_e( 'Description', 'mal-premium-lms' ); ?>"></textarea>
			<input type="hidden" name="video_attachment_id" data-media-target="video" />
			<input type="hidden" name="pdf_attachment_id" data-media-target="pdf" />
			<button type="button" data-media-upload="video"><?php esc_html_e( 'Select Video', 'mal-premium-lms' ); ?></button>
			<button type="button" data-media-upload="pdf"><?php esc_html_e( 'Select PDF', 'mal-premium-lms' ); ?></button>
			<button class="mal-lms-primary" type="submit"><?php esc_html_e( 'Save Course', 'mal-premium-lms' ); ?></button>
		</form>
	</div>
</div>
<div class="mal-lms-modal mal-lms-modal--danger" id="confirm-modal" aria-hidden="true">
	<div class="mal-lms-modal__panel" role="dialog" aria-modal="true"><h2><?php esc_html_e( 'Confirm destructive action', 'mal-premium-lms' ); ?></h2><p><?php esc_html_e( 'This action cannot be undone.', 'mal-premium-lms' ); ?></p><button class="mal-lms-danger" data-confirm-action><?php esc_html_e( 'Confirm', 'mal-premium-lms' ); ?></button><button data-close-modal><?php esc_html_e( 'Cancel', 'mal-premium-lms' ); ?></button></div>
</div>
<div class="mal-lms-modal" id="receipt-modal" aria-hidden="true">
	<div class="mal-lms-modal__panel" role="dialog" aria-modal="true"><button class="mal-lms-modal__close" data-close-modal type="button">×</button><div data-receipt-preview></div><button data-print-modal><?php esc_html_e( 'Print / PDF', 'mal-premium-lms' ); ?></button><button data-share-modal><?php esc_html_e( 'Share', 'mal-premium-lms' ); ?></button></div>
</div>

<div class="mal-lms-modal" id="certificate-modal" aria-hidden="true">
	<div class="mal-lms-modal__panel mal-lms-certificate-preview" role="dialog" aria-modal="true">
		<button class="mal-lms-modal__close" data-close-modal type="button">×</button>
		<p class="mal-lms-eyebrow">Certificate of Completion</p>
		<h2><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h2>
		<p><?php esc_html_e( 'This premium certificate template is ready for browser print, jsPDF export, and future automated issuance from result records.', 'mal-premium-lms' ); ?></p>
		<button class="mal-lms-primary" data-print-modal type="button"><?php esc_html_e( 'Generate PDF / Print', 'mal-premium-lms' ); ?></button>
	</div>
</div>
