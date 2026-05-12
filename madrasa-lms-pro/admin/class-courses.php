<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class MLP_Courses { public static function render(): void { mlp_render_view( 'courses', array( 'title' => __( 'Courses', 'madrasa-lms-pro' ), 'resource' => 'courses', 'rows' => MLP_DB::rows( 'courses' ) ) ); } }
