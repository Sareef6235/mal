<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class MLP_Teachers { public static function render(): void { mlp_render_view( 'teachers', array( 'rows' => MLP_DB::rows( 'teachers' ) ) ); } }
