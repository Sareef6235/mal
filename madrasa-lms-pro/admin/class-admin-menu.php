<?php
/** Admin menus and CRUD controller. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MLP_Admin_Menu {
	public function hooks(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_post_mlp_save', array( $this, 'handle_save' ) );
		add_action( 'admin_post_mlp_delete', array( $this, 'handle_delete' ) );
		add_action( 'wp_ajax_mlp_save_attendance', array( 'MLP_Attendance', 'ajax_save' ) );
		add_filter( 'upload_mimes', array( 'MLP_Security', 'allowed_upload_mimes' ) );
	}
	public function menu(): void {
		add_menu_page( __( 'Madrasa', 'madrasa-lms-pro' ), __( 'Madrasa', 'madrasa-lms-pro' ), 'manage_mlp', 'mlp-dashboard', array( 'MLP_Dashboard', 'render' ), 'dashicons-welcome-learn-more', 26 );
		$items = array( 'students' => 'Students', 'teachers' => 'Teachers', 'courses' => 'Courses', 'attendance' => 'Attendance', 'exams' => 'Exams', 'results' => 'Results', 'monthly_plans' => 'Monthly Plans', 'settings' => 'Settings' );
		foreach ( $items as $slug => $label ) { add_submenu_page( 'mlp-dashboard', __( $label, 'madrasa-lms-pro' ), __( $label, 'madrasa-lms-pro' ), 'manage_mlp', 'mlp-' . str_replace( '_', '-', $slug ), array( $this, 'page' ) ); }
	}
	public function assets( string $hook ): void {
		if ( false === strpos( $hook, 'mlp' ) ) { return; }
		wp_enqueue_style( 'mlp-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', array(), '5.3.3' );
		wp_enqueue_style( 'mlp-admin', MLP_URL . 'assets/css/admin.css', array(), MLP_VERSION );
		wp_enqueue_script( 'mlp-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', array(), '5.3.3', true );
		wp_enqueue_script( 'mlp-admin', MLP_URL . 'assets/js/admin.js', array(), MLP_VERSION, true );
		wp_enqueue_script( 'mlp-dashboard', MLP_URL . 'assets/js/dashboard.js', array(), MLP_VERSION, true );
		wp_localize_script( 'mlp-admin', 'MLPAdmin', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( MLP_Security::NONCE_ACTION ) ) );
	}
	public function page(): void {
		$page = sanitize_key( str_replace( 'mlp-', '', $_GET['page'] ?? 'dashboard' ) );
		$map  = array( 'students' => 'MLP_Students', 'teachers' => 'MLP_Teachers', 'courses' => 'MLP_Courses', 'attendance' => 'MLP_Attendance', 'exams' => 'MLP_Exams', 'results' => 'MLP_Results', 'monthly-plans' => 'MLP_Dashboard', 'settings' => 'MLP_Settings' );
		$class = $map[ $page ] ?? 'MLP_Dashboard';
		if ( 'monthly-plans' === $page ) { MLP_Dashboard::monthly_plans(); return; }
		call_user_func( array( $class, 'render' ) );
	}
	public function handle_save(): void { MLP_Security::verify_request(); $resource = sanitize_key( $_POST['resource'] ?? '' ); self::save_resource( $resource, $_POST ); wp_safe_redirect( wp_get_referer() ?: admin_url( 'admin.php?page=mlp-dashboard' ) ); exit; }
	public function handle_delete(): void { MLP_Security::verify_request(); global $wpdb; $resource = sanitize_key( $_GET['resource'] ?? '' ); $id = absint( $_GET['id'] ?? 0 ); if ( $resource && $id ) { $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . esc_sql( mlp_table( $resource ) ) . ' WHERE id=%d', $id ) ); } wp_safe_redirect( wp_get_referer() ?: admin_url( 'admin.php?page=mlp-dashboard' ) ); exit; }
	public static function save_resource( string $resource, array $data ): array {
		global $wpdb; $now = current_time( 'mysql' ); $id = absint( $data['id'] ?? 0 ); $table = mlp_table( $resource );
		$payload = self::sanitize_payload( $resource, $data, $now );
		if ( $id ) { $wpdb->update( $table, $payload, array( 'id' => $id ) ); } else { $wpdb->insert( $table, $payload ); $id = (int) $wpdb->insert_id; }
		return array( 'id' => $id, 'resource' => $resource );
	}
	private static function sanitize_payload( string $resource, array $data, string $now ): array {
		$common = array( 'updated_at' => $now );
		switch ( $resource ) {
			case 'students': return array_merge( array( 'student_no' => MLP_Security::clean_text( $data['student_no'] ?? uniqid( 'S' ) ), 'name' => MLP_Security::clean_text( $data['name'] ?? '' ), 'email' => sanitize_email( $data['email'] ?? '' ), 'phone' => MLP_Security::clean_text( $data['phone'] ?? '' ), 'class_name' => MLP_Security::clean_text( $data['class_name'] ?? '' ), 'status' => MLP_Security::clean_text( $data['status'] ?? 'active' ), 'created_at' => $now ), $common );
			case 'teachers': return array_merge( array( 'teacher_no' => MLP_Security::clean_text( $data['teacher_no'] ?? uniqid( 'T' ) ), 'name' => MLP_Security::clean_text( $data['name'] ?? '' ), 'email' => sanitize_email( $data['email'] ?? '' ), 'phone' => MLP_Security::clean_text( $data['phone'] ?? '' ), 'subjects' => MLP_Security::clean_textarea( $data['subjects'] ?? '' ), 'created_at' => $now ), $common );
			case 'courses': return array_merge( array( 'title' => MLP_Security::clean_text( $data['title'] ?? '' ), 'description' => wp_kses_post( $data['description'] ?? '' ), 'category' => MLP_Security::clean_text( $data['category'] ?? '' ), 'teacher_id' => absint( $data['teacher_id'] ?? 0 ), 'status' => MLP_Security::clean_text( $data['status'] ?? 'draft' ), 'created_at' => $now ), $common );
			case 'monthly_plans': return array_merge( array( 'plan_month' => MLP_Security::clean_text( $data['plan_month'] ?? '' ), 'teacher_id' => absint( $data['teacher_id'] ?? 0 ), 'class_name' => MLP_Security::clean_text( $data['class_name'] ?? '' ), 'week_no' => absint( $data['week_no'] ?? 1 ), 'total_period' => absint( $data['total_period'] ?? 0 ), 'subject' => MLP_Security::clean_text( $data['subject'] ?? '' ), 'lesson_name' => MLP_Security::clean_text( $data['lesson_name'] ?? '' ), 'lesson_details' => MLP_Security::clean_textarea( $data['lesson_details'] ?? '' ), 'activities' => MLP_Security::clean_textarea( $data['activities'] ?? '' ), 'smart_class_date' => MLP_Security::clean_text( $data['smart_class_date'] ?? '' ), 'exam_date' => MLP_Security::clean_text( $data['exam_date'] ?? '' ), 'created_at' => $now ), $common );
			case 'exams': return array( 'title' => MLP_Security::clean_text( $data['title'] ?? '' ), 'course_id' => absint( $data['course_id'] ?? 0 ), 'exam_date' => MLP_Security::clean_text( $data['exam_date'] ?? gmdate( 'Y-m-d' ) ), 'total_marks' => (float) ( $data['total_marks'] ?? 100 ), 'created_at' => $now );
			case 'results': $marks = (float) ( $data['marks'] ?? 0 ); return array( 'exam_id' => absint( $data['exam_id'] ?? 0 ), 'student_id' => absint( $data['student_id'] ?? 0 ), 'marks' => $marks, 'grade' => MLP_Utils::grade( $marks, 100 ), 'published' => absint( $data['published'] ?? 0 ), 'created_at' => $now );
		}
		return array();
	}
}
