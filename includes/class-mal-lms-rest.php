<?php
/**
 * REST API controller for MAL Premium LMS.
 *
 * @package MAL_Premium_LMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers mobile/PWA and React dashboard endpoints.
 */
class MAL_LMS_REST {

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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register LMS routes.
	 */
	public function register_routes() {
		register_rest_route(
			'mal-lms/v1',
			'/dashboard',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'dashboard' ),
				'permission_callback' => array( $this, 'can_view' ),
			)
		);

		register_rest_route(
			'mal-lms/v1',
			'/plans',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'plans' ),
					'permission_callback' => array( $this, 'can_view' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_plan' ),
					'permission_callback' => array( $this, 'can_manage_plans' ),
				),
			)
		);

		register_rest_route(
			'mal-lms/v1',
			'/receipts/(?P<id>\d+)/status',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'receipt_status' ),
				'permission_callback' => array( $this, 'can_manage_receipts' ),
				'args'                => array(
					'id'     => array( 'sanitize_callback' => 'absint' ),
					'status' => array( 'sanitize_callback' => 'sanitize_key' ),
				),
			)
		);
	}

	/**
	 * Check view permission.
	 *
	 * @return bool
	 */
	public function can_view() {
		return is_user_logged_in() && current_user_can( 'mal_lms_view_own_records' );
	}

	/**
	 * Check plan management permission.
	 *
	 * @return bool
	 */
	public function can_manage_plans() {
		return is_user_logged_in() && current_user_can( 'mal_lms_manage_plans' );
	}

	/**
	 * Check receipt management permission.
	 *
	 * @return bool
	 */
	public function can_manage_receipts() {
		return is_user_logged_in() && ( current_user_can( 'mal_lms_manage_receipts' ) || current_user_can( 'mal_lms_manage_all' ) );
	}

	/**
	 * Return dashboard payload.
	 *
	 * @return WP_REST_Response
	 */
	public function dashboard() {
		return rest_ensure_response(
			array(
				'counts'   => $this->repository->get_dashboard_counts(),
				'courses'  => $this->repository->get_courses_for_user( get_current_user_id() ),
				'plans'    => $this->repository->get_plans(),
				'receipts' => $this->repository->get_receipts(),
			)
		);
	}

	/**
	 * Return plans.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function plans( WP_REST_Request $request ) {
		return rest_ensure_response(
			$this->repository->get_plans(
				array(
					'class_name' => $request->get_param( 'class_name' ),
					'subject'    => $request->get_param( 'subject' ),
				)
			)
		);
	}

	/**
	 * Save plan from REST.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_plan( WP_REST_Request $request ) {
		$id = $this->repository->save_plan( $request->get_json_params() );
		if ( ! $id ) {
			return new WP_Error( 'mal_lms_plan_invalid', __( 'Plan title and class are required.', 'mal-premium-lms' ), array( 'status' => 400 ) );
		}
		return rest_ensure_response( array( 'id' => $id ) );
	}

	/**
	 * Update receipt status.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function receipt_status( WP_REST_Request $request ) {
		$updated = $this->repository->update_receipt_status( absint( $request['id'] ), sanitize_key( $request->get_param( 'status' ) ) );
		if ( ! $updated ) {
			return new WP_Error( 'mal_lms_receipt_status', __( 'Invalid receipt status.', 'mal-premium-lms' ), array( 'status' => 400 ) );
		}
		return rest_ensure_response( array( 'success' => true ) );
	}
}
