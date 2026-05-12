<?php
/**
 * Activation routines for MAL Premium LMS.
 *
 * @package MAL_Premium_LMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates roles, capabilities, pages, and database tables.
 */
class MAL_LMS_Activator {

	/**
	 * Run activation tasks.
	 */
	public static function activate() {
		self::create_roles();
		self::create_tables();
		self::create_pwa_pages();
		flush_rewrite_rules();
	}

	/**
	 * Run deactivation tasks.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Register Admin, Ustad, and Student capabilities.
	 */
	private static function create_roles() {
		$admin = get_role( 'administrator' );
		$capabilities = array(
			'mal_lms_manage_all',
			'mal_lms_manage_courses',
			'mal_lms_manage_plans',
			'mal_lms_manage_receipts',
			'mal_lms_manage_attendance',
			'mal_lms_manage_exams',
			'mal_lms_view_own_records',
		);

		if ( $admin ) {
			foreach ( $capabilities as $capability ) {
				$admin->add_cap( $capability );
			}
		}

		add_role(
			'ustad',
			__( 'Ustad', 'mal-premium-lms' ),
			array(
				'read'                      => true,
				'upload_files'              => true,
				'mal_lms_manage_courses'    => true,
				'mal_lms_manage_plans'      => true,
				'mal_lms_manage_attendance' => true,
				'mal_lms_manage_exams'      => true,
				'mal_lms_view_own_records'  => true,
			)
		);

		add_role(
			'mal_student',
			__( 'Student', 'mal-premium-lms' ),
			array(
				'read'                     => true,
				'mal_lms_view_own_records' => true,
			)
		);
	}

	/**
	 * Create normalized LMS and receipt tables using dbDelta.
	 */
	private static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$prefix          = $wpdb->prefix . 'mal_lms_';

		$sql = array();
		$sql[] = "CREATE TABLE {$prefix}courses (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			title VARCHAR(191) NOT NULL,
			description LONGTEXT NULL,
			class_name VARCHAR(100) NULL,
			subject VARCHAR(100) NULL,
			ustad_id BIGINT UNSIGNED NULL,
			video_attachment_id BIGINT UNSIGNED NULL,
			pdf_attachment_id BIGINT UNSIGNED NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY ustad_id (ustad_id),
			KEY class_subject (class_name, subject)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$prefix}plans (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			course_id BIGINT UNSIGNED NULL,
			ustad_id BIGINT UNSIGNED NULL,
			class_name VARCHAR(100) NOT NULL,
			subject VARCHAR(100) NOT NULL,
			title VARCHAR(191) NOT NULL,
			description TEXT NULL,
			week_number TINYINT UNSIGNED NOT NULL DEFAULT 1,
			plan_date DATE NULL,
			sort_order INT UNSIGNED NOT NULL DEFAULT 0,
			status VARCHAR(20) NOT NULL DEFAULT 'scheduled',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY course_id (course_id),
			KEY class_subject_week (class_name, subject, week_number)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$prefix}receipts (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			student_id BIGINT UNSIGNED NOT NULL,
			amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
			currency VARCHAR(10) NOT NULL DEFAULT 'USD',
			receipt_attachment_id BIGINT UNSIGNED NULL,
			note TEXT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			reviewed_by BIGINT UNSIGNED NULL,
			reviewed_at DATETIME NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY student_id (student_id),
			KEY status (status)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$prefix}attendance (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			course_id BIGINT UNSIGNED NULL,
			student_id BIGINT UNSIGNED NOT NULL,
			attendance_date DATE NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'present',
			note TEXT NULL,
			marked_by BIGINT UNSIGNED NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY student_course_date (student_id, course_id, attendance_date),
			KEY attendance_date (attendance_date)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$prefix}exams (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			course_id BIGINT UNSIGNED NULL,
			title VARCHAR(191) NOT NULL,
			exam_date DATE NULL,
			total_marks DECIMAL(8,2) NOT NULL DEFAULT 100.00,
			created_by BIGINT UNSIGNED NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY course_id (course_id)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$prefix}results (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			exam_id BIGINT UNSIGNED NOT NULL,
			student_id BIGINT UNSIGNED NOT NULL,
			marks_obtained DECIMAL(8,2) NOT NULL DEFAULT 0.00,
			grade VARCHAR(20) NULL,
			remarks TEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY exam_student (exam_id, student_id),
			KEY student_id (student_id)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$prefix}certificates (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			student_id BIGINT UNSIGNED NOT NULL,
			course_id BIGINT UNSIGNED NULL,
			certificate_code VARCHAR(100) NOT NULL,
			pdf_attachment_id BIGINT UNSIGNED NULL,
			issued_at DATETIME NOT NULL,
			issued_by BIGINT UNSIGNED NULL,
			PRIMARY KEY (id),
			UNIQUE KEY certificate_code (certificate_code),
			KEY student_course (student_id, course_id)
		) {$charset_collate};";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}

		update_option( 'mal_lms_db_version', MAL_LMS_VERSION );
	}

	/**
	 * Create a starter student dashboard page if one does not exist.
	 */
	private static function create_pwa_pages() {
		if ( get_option( 'mal_lms_dashboard_page_id' ) ) {
			return;
		}

		$page_id = wp_insert_post(
			array(
				'post_title'   => __( 'Student LMS Dashboard', 'mal-premium-lms' ),
				'post_content' => '[mal_lms_dashboard]',
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_option( 'mal_lms_dashboard_page_id', absint( $page_id ) );
		}
	}
}
