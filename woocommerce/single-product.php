<?php
/**
 * WooCommerce single product.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header( 'shop' ); ?>
<section class="uesp-section"><div class="uesp-container uesp-card"><?php while ( have_posts() ) { the_post(); wc_get_template_part( 'content', 'single-product' ); } ?></div></section>
<?php get_footer( 'shop' ); ?>
