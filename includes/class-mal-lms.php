<?php
/**
 * Main orchestration class.
 *
 * @package MAL_Premium_LMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once MAL_LMS_PATH . 'includes/class-mal-lms-repository.php';
require_once MAL_LMS_PATH . 'includes/class-mal-lms-rest.php';
require_once MAL_LMS_PATH . 'admin/class-mal-lms-admin.php';
require_once MAL_LMS_PATH . 'public/class-mal-lms-public.php';

/**
 * Coordinates admin, public, REST, and PWA features.
 */
class MAL_LMS {

	/**
	 * Repository instance.
	 *
	 * @var MAL_LMS_Repository
	 */
	private $repository;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repository = new MAL_LMS_Repository();
	}

	/**
	 * Register all WordPress hooks.
	 */
	public function run() {
		load_plugin_textdomain( 'mal-premium-lms', false, dirname( MAL_LMS_BASENAME ) . '/languages' );

		$admin  = new MAL_LMS_Admin( $this->repository );
		$public = new MAL_LMS_Public( $this->repository );
		$rest   = new MAL_LMS_REST( $this->repository );

		$admin->hooks();
		$public->hooks();
		$rest->hooks();

		add_action( 'init', array( $this, 'register_pwa_routes' ) );
		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'serve_pwa_assets' ) );
	}

	/**
	 * Add rewrite endpoints for manifest and service worker.
	 */
	public function register_pwa_routes() {
		add_rewrite_rule( '^mal-lms-manifest\.json$', 'index.php?mal_lms_manifest=1', 'top' );
		add_rewrite_rule( '^mal-lms-sw\.js$', 'index.php?mal_lms_sw=1', 'top' );
	}

	/**
	 * Register query vars for PWA assets.
	 *
	 * @param array<int,string> $vars Query vars.
	 * @return array<int,string>
	 */
	public function register_query_vars( $vars ) {
		$vars[] = 'mal_lms_manifest';
		$vars[] = 'mal_lms_sw';
		return $vars;
	}

	/**
	 * Output dynamic manifest/service worker responses.
	 */
	public function serve_pwa_assets() {
		if ( get_query_var( 'mal_lms_manifest' ) ) {
			header( 'Content-Type: application/manifest+json; charset=utf-8' );
			echo wp_json_encode(
				array(
					'name'             => get_bloginfo( 'name' ) . ' LMS',
					'short_name'       => 'MAL LMS',
					'start_url'        => home_url( '/' ),
					'display'          => 'standalone',
					'background_color' => '#070914',
					'theme_color'      => '#6d5dfc',
					'icons'            => array(),
				)
			);
			exit;
		}

		if ( get_query_var( 'mal_lms_sw' ) ) {
			header( 'Content-Type: application/javascript; charset=utf-8' );
			?>
const MAL_LMS_CACHE = 'mal-lms-v1';
self.addEventListener('install', event => event.waitUntil(caches.open(MAL_LMS_CACHE).then(cache => cache.addAll(['/']))));
self.addEventListener('fetch', event => event.respondWith(fetch(event.request).catch(() => caches.match(event.request))));
			<?php
			exit;
		}
	}
}
