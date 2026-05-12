<?php
/** Frontend assets. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class MLP_Frontend { public function hooks(): void { add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) ); } public function assets(): void { wp_enqueue_style( 'mlp-frontend', MLP_URL . 'assets/css/frontend.css', array(), MLP_VERSION ); wp_enqueue_style( 'mlp-pwa', MLP_URL . 'assets/css/pwa.css', array(), MLP_VERSION ); wp_enqueue_script( 'mlp-frontend', MLP_URL . 'assets/js/frontend.js', array(), MLP_VERSION, true ); wp_localize_script( 'mlp-frontend', 'MLPFrontend', array( 'rest' => esc_url_raw( rest_url( 'mlp/v1/' ) ), 'nonce' => wp_create_nonce( 'wp_rest' ) ) ); } }
