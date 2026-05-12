<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="mlp-frontend-card"><h2><?php esc_html_e( 'Results', 'madrasa-lms-pro' ); ?></h2><table><tbody><?php foreach ( $rows as $row ) : ?><tr><td><?php echo esc_html( $row['exam_id'] ); ?></td><td><?php echo esc_html( $row['marks'] ); ?></td><td><?php echo esc_html( $row['grade'] ); ?></td></tr><?php endforeach; ?></tbody></table></div>
