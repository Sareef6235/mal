<?php
/** Roles and capabilities. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MLP_Roles {
	public static function add_roles(): void {
		add_role( 'mlp_admin', __( 'LMS Admin', 'madrasa-lms-pro' ), self::admin_caps() );
		add_role( 'mlp_teacher', __( 'Ustad / Teacher', 'madrasa-lms-pro' ), self::teacher_caps() );
		add_role( 'mlp_student', __( 'Student', 'madrasa-lms-pro' ), self::student_caps() );
		self::register_caps();
	}
	public static function remove_roles(): void { remove_role( 'mlp_admin' ); remove_role( 'mlp_teacher' ); remove_role( 'mlp_student' ); }
	public static function register_caps(): void {
		$admin = get_role( 'administrator' );
		if ( $admin ) { foreach ( array_keys( self::admin_caps() ) as $cap ) { $admin->add_cap( $cap ); } }
	}
	private static function admin_caps(): array { return array( 'read' => true, 'manage_mlp' => true, 'mlp_manage_students' => true, 'mlp_manage_teachers' => true, 'mlp_manage_courses' => true, 'mlp_manage_attendance' => true, 'mlp_manage_exams' => true, 'mlp_manage_settings' => true ); }
	private static function teacher_caps(): array { return array( 'read' => true, 'mlp_teacher' => true, 'mlp_manage_courses' => true, 'mlp_manage_attendance' => true, 'mlp_manage_exams' => true ); }
	private static function student_caps(): array { return array( 'read' => true, 'mlp_student' => true, 'mlp_view_courses' => true, 'mlp_view_results' => true ); }
}
