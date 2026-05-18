<?php
/**
 * Comments template.
 *
 * @package UESP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="uesp-card" style="margin-top:24px">
	<?php if ( have_comments() ) : ?>
		<h2><?php printf( esc_html( _nx( 'One discussion', '%1$s discussions', get_comments_number(), 'comments title', 'uesp-theme' ) ), esc_html( number_format_i18n( get_comments_number() ) ) ); ?></h2>
		<ol class="comment-list"><?php wp_list_comments( array( 'style' => 'ol', 'short_ping' => true, 'avatar_size' => 48 ) ); ?></ol>
		<div class="uesp-pagination"><?php paginate_comments_links(); ?></div>
	<?php endif; ?>
	<?php if ( ! comments_open() && get_comments_number() ) : ?><p><?php esc_html_e( 'Comments are closed.', 'uesp-theme' ); ?></p><?php endif; ?>
	<?php comment_form(); ?>
</section>
