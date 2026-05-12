<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class MLP_Students { public static function render(): void { mlp_render_view( 'students', array( 'rows' => MLP_DB::rows( 'students' ) ) ); } }
