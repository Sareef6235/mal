<?php
/**
 * Data access layer for MAL Premium LMS.
 *
 * @package MAL_Premium_LMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repository wraps all database writes in sanitized, prepared WPDB calls.
 */
class MAL_LMS_Repository {

	/**
	 * Return a plugin table name.
	 *
	 * @param string $name Table suffix.
	 * @return string
	 */
	public static function table( $name ) {
		global $wpdb;
		return $wpdb->prefix . 'mal_lms_' . sanitize_key( $name );
	}

	/**
	 * Get dashboard KPI counts.
	 *
	 * @return array<string,int>
	 */
	public function get_dashboard_counts() {
		global $wpdb;
		return array(
			'courses'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . self::table( 'courses' ) ),
			'plans'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . self::table( 'plans' ) ),
			'receipts'  => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM " . self::table( 'receipts' ) . ' WHERE status = %s', 'pending' ) ),
			'attendees' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . self::table( 'attendance' ) ),
		);
	}

	/**
	 * List courses visible to the current user.
	 *
	 * @param int $user_id User ID.
	 * @return array<int,object>
	 */
	public function get_courses_for_user( $user_id ) {
		global $wpdb;
		$table = self::table( 'courses' );

		if ( current_user_can( 'mal_lms_manage_all' ) ) {
			return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 100" );
		}

		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE ustad_id = %d ORDER BY created_at DESC LIMIT 100", $user_id ) );
	}

	/**
	 * Upsert a course.
	 *
	 * @param array<string,mixed> $data Course data.
	 * @return int|false
	 */
	public function save_course( $data ) {
		global $wpdb;
		$now   = current_time( 'mysql' );
		$table = self::table( 'courses' );
		$row   = array(
			'title'               => sanitize_text_field( $data['title'] ?? '' ),
			'description'         => wp_kses_post( $data['description'] ?? '' ),
			'class_name'          => sanitize_text_field( $data['class_name'] ?? '' ),
			'subject'             => sanitize_text_field( $data['subject'] ?? '' ),
			'ustad_id'            => absint( $data['ustad_id'] ?? get_current_user_id() ),
			'video_attachment_id' => absint( $data['video_attachment_id'] ?? 0 ),
			'pdf_attachment_id'   => absint( $data['pdf_attachment_id'] ?? 0 ),
			'status'              => sanitize_key( $data['status'] ?? 'active' ),
			'updated_at'          => $now,
		);

		if ( empty( $row['title'] ) ) {
			return false;
		}

		if ( ! empty( $data['id'] ) ) {
			$result = $wpdb->update( $table, $row, array( 'id' => absint( $data['id'] ) ) );
			return false === $result ? false : absint( $data['id'] );
		}

		$row['created_at'] = $now;
		$result            = $wpdb->insert( $table, $row );
		return false === $result ? false : (int) $wpdb->insert_id;
	}

	/**
	 * Save a monthly plan item.
	 *
	 * @param array<string,mixed> $data Plan data.
	 * @return int|false
	 */
	public function save_plan( $data ) {
		global $wpdb;
		$now   = current_time( 'mysql' );
		$table = self::table( 'plans' );
		$row   = array(
			'course_id'   => absint( $data['course_id'] ?? 0 ),
			'ustad_id'    => absint( $data['ustad_id'] ?? get_current_user_id() ),
			'class_name'  => sanitize_text_field( $data['class_name'] ?? '' ),
			'subject'     => sanitize_text_field( $data['subject'] ?? '' ),
			'title'       => sanitize_text_field( $data['title'] ?? '' ),
			'description' => sanitize_textarea_field( $data['description'] ?? '' ),
			'week_number' => absint( $data['week_number'] ?? 1 ),
			'plan_date'   => sanitize_text_field( $data['plan_date'] ?? '' ),
			'sort_order'  => absint( $data['sort_order'] ?? 0 ),
			'status'      => sanitize_key( $data['status'] ?? 'scheduled' ),
			'updated_at'  => $now,
		);

		if ( empty( $row['title'] ) || empty( $row['class_name'] ) ) {
			return false;
		}

		if ( ! empty( $data['id'] ) ) {
			$result = $wpdb->update( $table, $row, array( 'id' => absint( $data['id'] ) ) );
			return false === $result ? false : absint( $data['id'] );
		}

		$row['created_at'] = $now;
		$result            = $wpdb->insert( $table, $row );
		return false === $result ? false : (int) $wpdb->insert_id;
	}

	/**
	 * Reorder plan cards after SortableJS drag/drop.
	 *
	 * @param array<int,int> $ordered_ids Ordered plan IDs.
	 * @return void
	 */
	public function reorder_plans( $ordered_ids ) {
		global $wpdb;
		$table = self::table( 'plans' );
		foreach ( array_values( $ordered_ids ) as $index => $plan_id ) {
			$wpdb->update( $table, array( 'sort_order' => absint( $index ) ), array( 'id' => absint( $plan_id ) ) );
		}
	}

	/**
	 * Get plans with optional filters.
	 *
	 * @param array<string,string> $filters Filters.
	 * @return array<int,object>
	 */
	public function get_plans( $filters = array() ) {
		global $wpdb;
		$table  = self::table( 'plans' );
		$where  = 'WHERE 1=1';
		$params = array();

		if ( ! empty( $filters['class_name'] ) ) {
			$where   .= ' AND class_name = %s';
			$params[] = sanitize_text_field( $filters['class_name'] );
		}
		if ( ! empty( $filters['subject'] ) ) {
			$where   .= ' AND subject = %s';
			$params[] = sanitize_text_field( $filters['subject'] );
		}
		if ( ! current_user_can( 'mal_lms_manage_all' ) ) {
			$where   .= ' AND ustad_id = %d';
			$params[] = get_current_user_id();
		}

		$query = "SELECT * FROM {$table} {$where} ORDER BY week_number ASC, sort_order ASC, id DESC LIMIT 200";
		return $params ? $wpdb->get_results( $wpdb->prepare( $query, $params ) ) : $wpdb->get_results( $query );
	}

	/**
	 * Save a receipt upload request.
	 *
	 * @param array<string,mixed> $data Receipt data.
	 * @return int|false
	 */
	public function save_receipt( $data ) {
		global $wpdb;
		$now   = current_time( 'mysql' );
		$table = self::table( 'receipts' );
		$row   = array(
			'student_id'             => absint( $data['student_id'] ?? get_current_user_id() ),
			'amount'                 => (float) ( $data['amount'] ?? 0 ),
			'currency'               => sanitize_text_field( $data['currency'] ?? 'USD' ),
			'receipt_attachment_id'  => absint( $data['receipt_attachment_id'] ?? 0 ),
			'note'                   => sanitize_textarea_field( $data['note'] ?? '' ),
			'status'                 => sanitize_key( $data['status'] ?? 'pending' ),
			'updated_at'             => $now,
		);
		$row['created_at'] = $now;

		$result = $wpdb->insert( $table, $row );
		return false === $result ? false : (int) $wpdb->insert_id;
	}

	/**
	 * List receipts, scoped by role.
	 *
	 * @return array<int,object>
	 */
	public function get_receipts() {
		global $wpdb;
		$table = self::table( 'receipts' );

		if ( current_user_can( 'mal_lms_manage_receipts' ) || current_user_can( 'mal_lms_manage_all' ) ) {
			return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 200" );
		}

		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE student_id = %d ORDER BY created_at DESC LIMIT 100", get_current_user_id() ) );
	}

	/**
	 * Change receipt status.
	 *
	 * @param int    $receipt_id Receipt ID.
	 * @param string $status New status.
	 * @return bool
	 */
	public function update_receipt_status( $receipt_id, $status ) {
		global $wpdb;
		$allowed = array( 'approved', 'pending', 'rejected' );
		if ( ! in_array( $status, $allowed, true ) ) {
			return false;
		}

		$result = $wpdb->update(
			self::table( 'receipts' ),
			array(
				'status'      => $status,
				'reviewed_by' => get_current_user_id(),
				'reviewed_at' => current_time( 'mysql' ),
				'updated_at'  => current_time( 'mysql' ),
			),
			array( 'id' => absint( $receipt_id ) )
		);

		return false !== $result;
	}

	/**
	 * Delete one or more receipts.
	 *
	 * @param array<int,int> $receipt_ids Receipt IDs.
	 * @return int
	 */
	public function delete_receipts( $receipt_ids ) {
		global $wpdb;
		$deleted = 0;
		foreach ( $receipt_ids as $receipt_id ) {
			$result = $wpdb->delete( self::table( 'receipts' ), array( 'id' => absint( $receipt_id ) ) );
			if ( $result ) {
				$deleted += (int) $result;
			}
		}
		return $deleted;
	}
}
