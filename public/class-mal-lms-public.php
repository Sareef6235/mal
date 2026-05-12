<?php
/**
 * Public shortcodes and frontend assets.
 *
 * @package MAL_Premium_LMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend/PWA controller.
 */
class MAL_LMS_Public {

	/**
	 * Repository instance.
	 *
	 * @var MAL_LMS_Repository
	 */
	private $repository;

	/**
	 * Constructor.
	 *
	 * @param MAL_LMS_Repository $repository Repository.
	 */
	public function __construct( MAL_LMS_Repository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Register hooks.
	 */
	public function hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
		add_shortcode( 'mal_lms_dashboard', array( $this, 'dashboard_shortcode' ) );
		add_shortcode( 'mal_lms_courses', array( $this, 'courses_shortcode' ) );
		add_shortcode( 'mal_lms_receipts', array( $this, 'receipts_shortcode' ) );
		add_shortcode( 'mal_lms_certificate', array( $this, 'certificate_shortcode' ) );
		add_action( 'wp_ajax_mal_lms_upload_receipt', array( $this, 'ajax_upload_receipt' ) );
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function assets() {
		wp_enqueue_style( 'mal-lms-public', MAL_LMS_URL . 'assets/css/public.css', array(), MAL_LMS_VERSION );
		wp_enqueue_script( 'mal-lms-public', MAL_LMS_URL . 'assets/js/public.js', array(), MAL_LMS_VERSION, true );
		wp_localize_script(
			'mal-lms-public',
			'MALLMSPublic',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'mal_lms_public' ),
				'swUrl'   => home_url( '/mal-lms-sw.js' ),
			)
		);
	}

	/**
	 * Render secure student dashboard shortcode.
	 *
	 * @return string
	 */
	public function dashboard_shortcode() {
		if ( ! is_user_logged_in() ) {
			return wp_login_form( array( 'echo' => false ) );
		}

		ob_start();
		?>
		<div class="mal-lms-public-dashboard">
			<h2><?php esc_html_e( 'My LMS Dashboard', 'mal-premium-lms' ); ?></h2>
			<?php echo wp_kses_post( $this->courses_shortcode() ); ?>
			<?php echo wp_kses_post( $this->receipts_shortcode() ); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render courses shortcode.
	 *
	 * @return string
	 */
	public function courses_shortcode() {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$courses = $this->repository->get_courses_for_user( get_current_user_id() );
		ob_start();
		?>
		<section class="mal-lms-public-card"><h3><?php esc_html_e( 'Courses', 'mal-premium-lms' ); ?></h3><div class="mal-lms-course-grid">
		<?php foreach ( $courses as $course ) : ?>
			<article><h4><?php echo esc_html( $course->title ); ?></h4><p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $course->description ), 24 ) ); ?></p><?php if ( $course->pdf_attachment_id ) : ?><a href="<?php echo esc_url( wp_get_attachment_url( (int) $course->pdf_attachment_id ) ); ?>"><?php esc_html_e( 'Open PDF', 'mal-premium-lms' ); ?></a><?php endif; ?></article>
		<?php endforeach; ?>
		</div></section>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render receipts shortcode and upload form.
	 *
	 * @return string
	 */
	public function receipts_shortcode() {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$receipts = $this->repository->get_receipts();
		ob_start();
		?>
		<section class="mal-lms-public-card"><h3><?php esc_html_e( 'My Receipts', 'mal-premium-lms' ); ?></h3>
		<form class="mal-lms-receipt-upload" enctype="multipart/form-data"><input type="number" step="0.01" name="amount" placeholder="<?php esc_attr_e( 'Amount', 'mal-premium-lms' ); ?>" required /><input type="file" name="receipt" accept="image/*,application/pdf" required /><button type="submit"><?php esc_html_e( 'Upload Receipt', 'mal-premium-lms' ); ?></button></form>
		<ul><?php foreach ( $receipts as $receipt ) : ?><li><?php echo esc_html( $receipt->currency . ' ' . $receipt->amount ); ?> <span class="mal-lms-public-status"><?php echo esc_html( ucfirst( $receipt->status ) ); ?></span></li><?php endforeach; ?></ul></section>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a printable certificate shell. Production sites can pass issued certificate data through attributes.
	 *
	 * @param array<string,string> $atts Shortcode attributes.
	 * @return string
	 */
	public function certificate_shortcode( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$atts = shortcode_atts(
			array(
				'course' => __( 'Course Completion', 'mal-premium-lms' ),
				'code'   => strtoupper( wp_generate_password( 10, false, false ) ),
			),
			$atts,
			'mal_lms_certificate'
		);

		ob_start();
		?>
		<section class="mal-lms-public-card mal-lms-certificate">
			<p><?php esc_html_e( 'Certificate of Completion', 'mal-premium-lms' ); ?></p>
			<h2><?php echo esc_html( wp_get_current_user()->display_name ); ?></h2>
			<h3><?php echo esc_html( $atts['course'] ); ?></h3>
			<small><?php echo esc_html( $atts['code'] ); ?></small>
			<button type="button" onclick="window.print()"><?php esc_html_e( 'Download PDF / Print', 'mal-premium-lms' ); ?></button>
		</section>
		<?php
		return ob_get_clean();
	}

	/**
	 * AJAX: secure receipt upload with nonce and WordPress media handling.
	 */
	public function ajax_upload_receipt() {
		check_ajax_referer( 'mal_lms_public', 'nonce' );

		if ( ! is_user_logged_in() || ! current_user_can( 'mal_lms_view_own_records' ) ) {
			wp_send_json_error( array( 'message' => __( 'Please log in.', 'mal-premium-lms' ) ), 403 );
		}

		if ( empty( $_FILES['receipt'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Receipt file is required.', 'mal-premium-lms' ) ), 400 );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = media_handle_upload( 'receipt', 0 );
		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ), 400 );
		}

		$id = $this->repository->save_receipt(
			array(
				'amount'                => isset( $_POST['amount'] ) ? (float) wp_unslash( $_POST['amount'] ) : 0,
				'receipt_attachment_id' => $attachment_id,
			)
		);

		wp_send_json_success( array( 'id' => $id ) );
	}
}
