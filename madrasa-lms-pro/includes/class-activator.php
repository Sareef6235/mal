<?php
/** Activation tasks. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MLP_Activator {
	public static function activate(): void {
		MLP_DB::create_tables();
		MLP_Roles::add_roles();
		add_option( 'mlp_settings', array( 'institute_name' => get_bloginfo( 'name' ), 'pwa_enabled' => 1, 'primary_color' => '#198754' ) );
		flush_rewrite_rules();
	}
}
