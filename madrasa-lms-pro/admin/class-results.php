<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class MLP_Results { public static function render(): void { mlp_render_view( 'results', array( 'rows' => MLP_DB::rows( 'results' ) ) ); } }
