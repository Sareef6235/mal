<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="mlp-frontend-card"><h2><?php esc_html_e( 'Attendance', 'madrasa-lms-pro' ); ?></h2><table><tbody><?php foreach ( $rows as $row ) : ?><tr><td><?php echo esc_html( $row['attendance_date'] ); ?></td><td><?php echo esc_html( $row['status'] ); ?></td></tr><?php endforeach; ?></tbody></table></div>
