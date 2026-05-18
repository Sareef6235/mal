<?php
/**
 * WooCommerce product archive.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header( 'shop' ); ?>
<section class="uesp-section"><div class="uesp-container"><div class="uesp-card" style="margin-bottom:24px"><h1 class="uesp-entry-title"><?php woocommerce_page_title(); ?></h1><?php do_action( 'woocommerce_archive_description' ); ?></div><?php if ( woocommerce_product_loop() ) { do_action( 'woocommerce_before_shop_loop' ); woocommerce_product_loop_start(); if ( wc_get_loop_prop( 'total' ) ) { while ( have_posts() ) { the_post(); do_action( 'woocommerce_shop_loop' ); wc_get_template_part( 'content', 'product' ); } } woocommerce_product_loop_end(); do_action( 'woocommerce_after_shop_loop' ); } else { do_action( 'woocommerce_no_products_found' ); } ?></div></section>
<?php get_footer( 'shop' ); ?>
