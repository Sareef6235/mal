<?php
/** Frontend shortcodes. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class MLP_Shortcodes {
	public function hooks(): void { foreach ( array( 'dashboard', 'courses', 'attendance', 'results', 'certificate', 'monthly_plan' ) as $tag ) { add_shortcode( 'mlp_' . $tag, array( $this, 'render' ) ); } }
	public function render( array $atts, string $content = '', string $tag = '' ): string {
		ob_start();
		$view = str_replace( 'mlp_', '', $tag );
		$table = $view;
		if ( 'monthly_plan' === $view ) { $table = 'monthly_plans'; }
		if ( 'dashboard' === $view ) { $table = 'courses'; }
		if ( 'certificate' === $view ) { $table = 'certificates'; }
		mlp_render_view( str_replace( '_', '-', $view ), array( 'rows' => MLP_DB::rows( $table ) ), true );
		return ob_get_clean();
	}
}
