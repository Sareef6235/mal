<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap mlp-admin"><h1><?php esc_html_e( 'Madrasa Dashboard', 'madrasa-lms-pro' ); ?></h1><div class="mlp-toolbar"><button class="button" onclick="window.print()"><?php esc_html_e( 'Print', 'madrasa-lms-pro' ); ?></button><button class="button mlp-dark-toggle"><?php esc_html_e( 'Dark/Light', 'madrasa-lms-pro' ); ?></button></div>
<div class="row g-3 mt-2">
<?php foreach ( $stats as $label => $value ) : ?>
<div class="col-md-2"><div class="card mlp-card"><div class="card-body"><h3><?php echo esc_html( number_format_i18n( $value ) ); ?></h3><p><?php echo esc_html( ucwords( str_replace( '_', ' ', $label ) ) ); ?></p></div></div></div>
<?php endforeach; ?><div class="col-md-2"><div class="card mlp-card"><div class="card-body"><h3>0%</h3><p><?php esc_html_e( 'Attendance %', 'madrasa-lms-pro' ); ?></p></div></div></div></div>
<div class="row mt-4"><div class="col-lg-8"><div class="card"><div class="card-body"><h2><?php esc_html_e( 'Analytics', 'madrasa-lms-pro' ); ?></h2><canvas id="mlpChart" height="120"></canvas></div></div></div><div class="col-lg-4"><div class="card"><div class="card-body"><h2><?php esc_html_e( 'Recent Activities', 'madrasa-lms-pro' ); ?></h2><ul><li><?php esc_html_e( 'System ready for LMS operations.', 'madrasa-lms-pro' ); ?></li></ul></div></div></div></div></div>
