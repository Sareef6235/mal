<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="mlp-grid"><?php foreach ( $rows as $row ) : ?><article class="mlp-frontend-card"><h3><?php echo esc_html( $row['title'] ?? '' ); ?></h3><p><?php echo wp_kses_post( wp_trim_words( $row['description'] ?? '', 24 ) ); ?></p></article><?php endforeach; ?></div>
