<?php
/** PWA support. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MLP_PWA {
	public function hooks(): void { add_action( 'init', array( $this, 'routes' ) ); add_action( 'wp_head', array( $this, 'manifest_link' ) ); add_action( 'admin_head', array( $this, 'manifest_link' ) ); }
	public function routes(): void {
		if ( isset( $_GET['mlp_manifest'] ) ) { $this->manifest(); }
		if ( isset( $_GET['mlp_sw'] ) ) { $this->service_worker(); }
	}
	public function manifest_link(): void { $settings = get_option( 'mlp_settings', array() ); if ( ! empty( $settings['pwa_enabled'] ) ) { echo '<link rel="manifest" href="' . esc_url( home_url( '/?mlp_manifest=1' ) ) . '"><script>if("serviceWorker" in navigator){navigator.serviceWorker.register("' . esc_url( home_url( '/?mlp_sw=1' ) ) . '");}</script>'; } }
	private function manifest(): void { nocache_headers(); header( 'Content-Type: application/manifest+json' ); echo wp_json_encode( array( 'name' => 'Madrasa', 'short_name' => 'Madrasa', 'start_url' => home_url( '/' ), 'display' => 'standalone', 'theme_color' => '#198754', 'background_color' => '#ffffff', 'icons' => array() ) ); exit; }
	private function service_worker(): void { nocache_headers(); header( 'Content-Type: application/javascript' ); echo "const MLP_CACHE='mlp-v1';self.addEventListener('install',e=>e.waitUntil(caches.open(MLP_CACHE).then(c=>c.addAll(['/']))));self.addEventListener('fetch',e=>e.respondWith(fetch(e.request).catch(()=>caches.match(e.request))));"; exit; }
}
