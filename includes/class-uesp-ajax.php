<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class UESP_Ajax {
    public static function init() {
        $actions = array( 'uesp_load_exam', 'uesp_save_answer', 'uesp_submit_exam', 'uesp_log_suspicious_event' );

        foreach ( $actions as $action ) {
            add_action( 'wp_ajax_' . $action, array( __CLASS__, str_replace( 'uesp_', '', $action ) ) );
            add_action( 'wp_ajax_nopriv_' . $action, array( __CLASS__, str_replace( 'uesp_', '', $action ) ) );
        }
    }

    private static function verify_nonce() {
        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

        if ( ! wp_verify_nonce( $nonce, 'uesp_exam_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'uesp' ) ), 403 );
        }
    }

    public static function load_exam() {
        self::verify_nonce();

        global $wpdb;
        $tables   = UESP_DB::tables();
        $category = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';

        if ( '' !== $category ) {
            $questions = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, question_text, option1, option2, option3, option4, marks FROM {$tables['questions']} WHERE category = %s ORDER BY RAND() LIMIT 10",
                    $category
                )
            );
        } else {
            $questions = $wpdb->get_results( "SELECT id, question_text, option1, option2, option3, option4, marks FROM {$tables['questions']} ORDER BY RAND() LIMIT 10" );
        }

        $payload = array();
        foreach ( (array) $questions as $question ) {
            $options = array();
            for ( $i = 1; $i <= 4; $i++ ) {
                $key         = 'option' . $i;
                $option_text = isset( $question->{$key} ) ? sanitize_text_field( $question->{$key} ) : '';

                if ( '' === $option_text ) {
                    continue;
                }

                $options[] = array(
                    'option_key'  => (string) $i,
                    'option_text' => $option_text,
                );
            }

            $payload[] = array(
                'id'            => absint( $question->id ),
                'question_text' => wp_kses_post( $question->question_text ),
                'marks'         => absint( $question->marks ),
                'options'       => $options,
            );
        }

        wp_send_json( array( 'questions' => $payload ) );
    }

    public static function save_answer() {
        self::verify_nonce();

        $question_id = isset( $_POST['question_id'] ) ? absint( $_POST['question_id'] ) : 0;
        $answer      = isset( $_POST['answer'] ) ? sanitize_text_field( wp_unslash( $_POST['answer'] ) ) : '';
        $client_id   = isset( $_POST['client_id'] ) ? sanitize_key( wp_unslash( $_POST['client_id'] ) ) : '';

        if ( ! $question_id || ! in_array( $answer, array( '1', '2', '3', '4' ), true ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid answer.', 'uesp' ) ), 400 );
        }

        $draft = self::get_answer_draft( $client_id );
        $draft[ $question_id ] = $answer;
        self::set_answer_draft( $client_id, $draft );

        wp_send_json_success( array( 'saved' => true ) );
    }

    public static function submit_exam() {
        self::verify_nonce();

        global $wpdb;
        $tables = UESP_DB::tables();

        $raw_answers = isset( $_POST['answers'] ) ? wp_unslash( $_POST['answers'] ) : '';
        $answers     = json_decode( $raw_answers, true );

        if ( ! is_array( $answers ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid answers payload.', 'uesp' ) ), 400 );
        }

        $clean_answers = array();
        foreach ( $answers as $question_id => $answer ) {
            $question_id = absint( $question_id );
            $answer      = sanitize_text_field( (string) $answer );

            if ( $question_id && in_array( $answer, array( '', '1', '2', '3', '4' ), true ) ) {
                $clean_answers[ $question_id ] = $answer;
            }
        }

        if ( empty( $clean_answers ) ) {
            wp_send_json_error( array( 'message' => __( 'No valid questions were submitted.', 'uesp' ) ), 400 );
        }

        $question_ids  = array_map( 'absint', array_keys( $clean_answers ) );
        $placeholders  = implode( ',', array_fill( 0, count( $question_ids ), '%d' ) );
        $questions_sql = $wpdb->prepare(
            "SELECT id, correct_answer, marks FROM {$tables['questions']} WHERE id IN ($placeholders)",
            $question_ids
        );
        $questions     = $wpdb->get_results( $questions_sql );

        if ( empty( $questions ) ) {
            wp_send_json_error( array( 'message' => __( 'Submitted questions could not be found.', 'uesp' ) ), 400 );
        }

        $total_score = 0;
        $answer_rows = array();

        foreach ( $questions as $question ) {
            $qid        = absint( $question->id );
            $answer     = isset( $clean_answers[ $qid ] ) ? $clean_answers[ $qid ] : '';
            $is_correct = ( (string) $question->correct_answer === (string) $answer ) ? 1 : 0;

            if ( $is_correct ) {
                $total_score += absint( $question->marks );
            }

            $answer_rows[] = array(
                'question_id' => $qid,
                'answer'      => $answer,
                'is_correct'  => $is_correct,
            );
        }

        $inserted = $wpdb->insert(
            $tables['attempts'],
            array(
                'user_id'     => get_current_user_id(),
                'total_score' => $total_score,
                'created_at'  => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s' )
        );

        if ( false === $inserted ) {
            wp_send_json_error( array( 'message' => __( 'Unable to save attempt.', 'uesp' ) ), 500 );
        }

        $attempt_id = absint( $wpdb->insert_id );

        foreach ( $answer_rows as $row ) {
            $wpdb->insert(
                $tables['answers'],
                array(
                    'attempt_id'  => $attempt_id,
                    'question_id' => $row['question_id'],
                    'answer'      => $row['answer'],
                    'is_correct'  => $row['is_correct'],
                ),
                array( '%d', '%d', '%s', '%d' )
            );
        }

        wp_send_json(
            array(
                'success'      => true,
                'attempt_id'   => $attempt_id,
                'redirect_url' => add_query_arg( 'attempt_id', $attempt_id, home_url( '/exam-result/' ) ),
            )
        );
    }

    public static function log_suspicious_event() {
        self::verify_nonce();

        global $wpdb;
        $tables     = UESP_DB::tables();
        $event_type = isset( $_POST['event_type'] ) ? sanitize_key( wp_unslash( $_POST['event_type'] ) ) : '';

        if ( '' === $event_type ) {
            wp_send_json_error( array( 'message' => __( 'Invalid event.', 'uesp' ) ), 400 );
        }

        $wpdb->insert(
            $tables['suspicious_logs'],
            array(
                'user_id'    => get_current_user_id(),
                'event_type' => $event_type,
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s' )
        );

        wp_send_json_success( array( 'logged' => true ) );
    }

    private static function get_answer_draft( $client_id ) {
        if ( is_user_logged_in() ) {
            $draft = get_user_meta( get_current_user_id(), '_uesp_answer_draft', true );
            return is_array( $draft ) ? $draft : array();
        }

        if ( '' === $client_id ) {
            return array();
        }

        $draft = get_transient( 'uesp_answer_draft_' . $client_id );
        return is_array( $draft ) ? $draft : array();
    }

    private static function set_answer_draft( $client_id, $draft ) {
        if ( is_user_logged_in() ) {
            update_user_meta( get_current_user_id(), '_uesp_answer_draft', $draft );
            return;
        }

        if ( '' !== $client_id ) {
            set_transient( 'uesp_answer_draft_' . $client_id, $draft, HOUR_IN_SECONDS );
        }
    }
}
