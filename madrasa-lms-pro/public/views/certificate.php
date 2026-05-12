<?php if ( ! defined( 'ABSPATH' ) ) { exit; } echo wp_kses_post( MLP_Certificates::html( sanitize_text_field( wp_unslash( $_GET['certificate_no'] ?? '' ) ) ) ); ?>
