<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class MLP_Exams { public static function render(): void { mlp_render_view( 'exams', array( 'rows' => MLP_DB::rows( 'exams' ) ) ); } }
