<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class MLP_Dashboard {
	public static function render(): void { mlp_render_view( 'dashboard', array( 'stats' => array( 'students' => MLP_DB::count( 'students' ), 'teachers' => MLP_DB::count( 'teachers' ), 'courses' => MLP_DB::count( 'courses' ), 'plans' => MLP_DB::count( 'monthly_plans' ), 'exams' => MLP_DB::count( 'exams' ) ) ) ); }
	public static function monthly_plans(): void { mlp_render_view( 'courses', array( 'title' => __( 'Monthly Plans', 'madrasa-lms-pro' ), 'resource' => 'monthly_plans', 'rows' => MLP_DB::rows( 'monthly_plans' ) ) ); }
}
