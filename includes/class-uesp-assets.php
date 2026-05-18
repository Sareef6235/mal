<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class UESP_Assets {
    public static function init() {
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_frontend_assets' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
    }

    public static function register_frontend_assets() {
        wp_register_style(
            'uesp-frontend',
            UESP_URL . 'assets/css/frontend.css',
            array(),
            UESP_VERSION
        );

        wp_register_script(
            'uesp-frontend',
            UESP_URL . 'assets/js/frontend.js',
            array(),
            UESP_VERSION,
            true
        );

        wp_localize_script(
            'uesp-frontend',
            'UESP_DATA',
            array(
                'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                'nonce'     => wp_create_nonce( 'uesp_exam_nonce' ),
                'resultUrl' => home_url( '/exam-result/' ),
                'duration'  => 30 * 60,
                'i18n'      => array(
                    'loading'         => __( 'Loading questions...', 'uesp' ),
                    'noQuestions'     => __( 'No questions found for this category.', 'uesp' ),
                    'submit'          => __( 'Submit Exam', 'uesp' ),
                    'submitting'      => __( 'Submitting...', 'uesp' ),
                    'finish'          => __( 'Finish Exam', 'uesp' ),
                    'previous'        => __( 'Previous', 'uesp' ),
                    'next'            => __( 'Next', 'uesp' ),
                    'confirmSubmit'   => __( 'Are you sure you want to submit your exam?', 'uesp' ),
                    'submitFailed'    => __( 'Unable to submit the exam. Please try again.', 'uesp' ),
                    'question'        => __( 'Question', 'uesp' ),
                    'of'              => __( 'of', 'uesp' ),
                    'mark'            => __( 'Mark', 'uesp' ),
                    'answered'        => __( 'Answered', 'uesp' ),
                    'unanswered'      => __( 'Unanswered', 'uesp' ),
                ),
            )
        );
    }

    public static function admin_assets( $hook ) {
        if ( false === strpos( (string) $hook, 'uesp' ) ) {
            return;
        }

        wp_enqueue_style( 'uesp-admin', UESP_URL . 'assets/css/admin.css', array(), UESP_VERSION );
    }
}
