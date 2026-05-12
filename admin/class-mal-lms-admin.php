<?php
/**
 * Admin dashboard and AJAX handlers.
 *
 * @package MAL_Premium_LMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Premium WordPress admin UI controller.
 */
class MAL_LMS_Admin {

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
	 * Register admin hooks.
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_mal_lms_save_course', array( $this, 'ajax_save_course' ) );
		add_action( 'wp_ajax_mal_lms_save_plan', array( $this, 'ajax_save_plan' ) );
		add_action( 'wp_ajax_mal_lms_reorder_plans', array( $this, 'ajax_reorder_plans' ) );
		add_action( 'wp_ajax_mal_lms_receipt_status', array( $this, 'ajax_receipt_status' ) );
		add_action( 'wp_ajax_mal_lms_delete_receipts', array( $this, 'ajax_delete_receipts' ) );
	}

	/**
	 * Register menu and settings pages.
	 */
	public function menu() {
		add_menu_page(
			__( 'MAL LMS', 'mal-premium-lms' ),
			__( 'MAL LMS', 'mal-premium-lms' ),
			'mal_lms_view_own_records',
			'mal-lms',
			array( $this, 'dashboard_page' ),
			'dashicons-welcome-learn-more',
			26
		);
		add_submenu_page( 'mal-lms', __( 'Dashboard', 'mal-premium-lms' ), __( 'Dashboard', 'mal-premium-lms' ), 'mal_lms_view_own_records', 'mal-lms', array( $this, 'dashboard_page' ) );
		add_submenu_page( 'mal-lms', __( 'Settings', 'mal-premium-lms' ), __( 'Settings', 'mal-premium-lms' ), 'manage_options', 'mal-lms-settings', array( $this, 'settings_page' ) );
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		register_setting(
			'mal_lms_settings',
			'mal_lms_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(
					'currency'          => 'USD',
					'certificate_title' => get_bloginfo( 'name' ),
					'theme'             => 'dark',
				),
			)
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array<string,string> $settings Settings.
	 * @return array<string,string>
	 */
	public function sanitize_settings( $settings ) {
		return array(
			'currency'          => sanitize_text_field( $settings['currency'] ?? 'USD' ),
			'certificate_title' => sanitize_text_field( $settings['certificate_title'] ?? '' ),
			'theme'             => sanitize_key( $settings['theme'] ?? 'dark' ),
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current hook.
	 */
	public function assets( $hook ) {
		if ( false === strpos( $hook, 'mal-lms' ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'mal-lms-admin', MAL_LMS_URL . 'assets/css/admin-dashboard.css', array(), MAL_LMS_VERSION );
		wp_enqueue_script( 'sortablejs', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js', array(), '1.15.2', true );
		wp_enqueue_script( 'jspdf', 'https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js', array(), '2.5.1', true );
		wp_enqueue_script( 'html2canvas', 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js', array(), '1.4.1', true );
		wp_enqueue_script( 'mal-lms-admin', MAL_LMS_URL . 'assets/js/admin-dashboard.js', array( 'jquery', 'sortablejs' ), MAL_LMS_VERSION, true );
		wp_localize_script(
			'mal-lms-admin',
			'MALLMS',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'restUrl' => esc_url_raw( rest_url( 'mal-lms/v1' ) ),
				'nonce'   => wp_create_nonce( 'mal_lms_admin' ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
				'i18n'    => array(
					'confirmDelete' => __( 'Delete selected receipts?', 'mal-premium-lms' ),
					'saved'         => __( 'Saved successfully.', 'mal-premium-lms' ),
				),
			)
		);
	}

	/**
	 * Render dashboard page.
	 */
	public function dashboard_page() {
		$counts   = $this->repository->get_dashboard_counts();
		$courses  = $this->repository->get_courses_for_user( get_current_user_id() );
		$plans    = $this->repository->get_plans();
		$receipts = $this->repository->get_receipts();
		include MAL_LMS_PATH . 'admin/views/dashboard.php';
	}

	/**
	 * Render settings page.
	 */
	public function settings_page() {
		$settings = get_option( 'mal_lms_settings', array() );
		include MAL_LMS_PATH . 'admin/views/settings.php';
	}

	/**
	 * Verify nonce and capability for AJAX.
	 *
	 * @param string $capability Required capability.
	 */
	private function verify_ajax( $capability ) {
		check_ajax_referer( 'mal_lms_admin', 'nonce' );
		if ( ! current_user_can( $capability ) && ! current_user_can( 'mal_lms_manage_all' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'mal-premium-lms' ) ), 403 );
		}
	}

	/**
	 * AJAX: save course.
	 */
	public function ajax_save_course() {
		$this->verify_ajax( 'mal_lms_manage_courses' );
		$id = $this->repository->save_course( wp_unslash( $_POST ) );
		$id ? wp_send_json_success( array( 'id' => $id ) ) : wp_send_json_error( array( 'message' => __( 'Course title is required.', 'mal-premium-lms' ) ), 400 );
	}

	/**
	 * AJAX: save plan.
	 */
	public function ajax_save_plan() {
		$this->verify_ajax( 'mal_lms_manage_plans' );
		$id = $this->repository->save_plan( wp_unslash( $_POST ) );
		$id ? wp_send_json_success( array( 'id' => $id ) ) : wp_send_json_error( array( 'message' => __( 'Plan title and class are required.', 'mal-premium-lms' ) ), 400 );
	}

	/**
	 * AJAX: reorder plans.
	 */
	public function ajax_reorder_plans() {
		$this->verify_ajax( 'mal_lms_manage_plans' );
		$ids = isset( $_POST['ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['ids'] ) ) : array();
		$this->repository->reorder_plans( $ids );
		wp_send_json_success();
	}

	/**
	 * AJAX: approve or reject receipt.
	 */
	public function ajax_receipt_status() {
		$this->verify_ajax( 'mal_lms_manage_receipts' );
		$updated = $this->repository->update_receipt_status( absint( $_POST['id'] ?? 0 ), sanitize_key( wp_unslash( $_POST['status'] ?? '' ) ) );
		$updated ? wp_send_json_success() : wp_send_json_error( array( 'message' => __( 'Invalid receipt status.', 'mal-premium-lms' ) ), 400 );
	}

	/**
	 * AJAX: bulk delete receipts.
	 */
	public function ajax_delete_receipts() {
		$this->verify_ajax( 'mal_lms_manage_receipts' );
		$ids     = isset( $_POST['ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['ids'] ) ) : array();
		$deleted = $this->repository->delete_receipts( $ids );
		wp_send_json_success( array( 'deleted' => $deleted ) );
	}
}
