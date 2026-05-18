<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class UESP_Shortcodes {
    public static function init() {
        add_shortcode( 'uesp_exam', array( __CLASS__, 'exam_shortcode' ) );
        add_shortcode( 'uesp_exam_result', array( __CLASS__, 'result_shortcode' ) );
    }

    public static function exam_shortcode() {
        global $wpdb;
        $tables = UESP_DB::tables();

        wp_enqueue_style( 'uesp-frontend' );
        wp_enqueue_script( 'uesp-frontend' );

        $categories = $wpdb->get_col( "SELECT DISTINCT category FROM {$tables['questions']} WHERE category IS NOT NULL AND category != '' ORDER BY category ASC" );

        ob_start();
        ?>
        <div class="uesp-app uesp-exam-shell" id="uesp-live-root">
            <header class="uesp-exam-header uesp-glass">
                <div class="uesp-brand">
                    <span class="uesp-brand-icon" aria-hidden="true">🎓</span>
                    <span><?php esc_html_e( 'Exam in Progress', 'uesp' ); ?></span>
                </div>

                <label class="uesp-filter-wrap" for="uesp-category-filter">
                    <span class="screen-reader-text"><?php esc_html_e( 'Filter exam category', 'uesp' ); ?></span>
                    <select id="uesp-category-filter" class="uesp-category-filter">
                        <option value=""><?php esc_html_e( 'All Categories', 'uesp' ); ?></option>
                        <?php foreach ( (array) $categories as $category ) : ?>
                            <option value="<?php echo esc_attr( $category ); ?>"><?php echo esc_html( $category ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div class="uesp-timer" id="uesp-timer" role="timer" aria-live="polite">30:00</div>

                <button id="uesp-submit-btn" class="uesp-btn uesp-btn-danger" type="button">
                    <?php esc_html_e( 'Submit', 'uesp' ); ?>
                </button>
            </header>

            <div id="uesp-status" class="uesp-status" aria-live="polite"></div>

            <main class="uesp-exam-main">
                <aside class="uesp-palette uesp-glass" aria-label="<?php esc_attr_e( 'Question navigation', 'uesp' ); ?>">
                    <div class="uesp-card-title"><?php esc_html_e( 'Questions', 'uesp' ); ?></div>
                    <div class="uesp-palette-legend">
                        <span><i class="uesp-dot answered"></i><?php esc_html_e( 'Answered', 'uesp' ); ?></span>
                        <span><i class="uesp-dot unanswered"></i><?php esc_html_e( 'Unanswered', 'uesp' ); ?></span>
                    </div>
                    <div class="uesp-palette-grid" id="uesp-palette"></div>
                </aside>

                <section class="uesp-question uesp-glass" id="uesp-question" aria-live="polite"></section>
            </main>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function result_shortcode() {
        global $wpdb;
        $tables     = UESP_DB::tables();
        $attempt_id = isset( $_GET['attempt_id'] ) ? absint( $_GET['attempt_id'] ) : 0;

        if ( ! $attempt_id ) {
            return '<div class="uesp-result uesp-glass"><h3>' . esc_html__( 'Result not found.', 'uesp' ) . '</h3></div>';
        }

        $attempt = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$tables['attempts']} WHERE id = %d", $attempt_id )
        );

        if ( ! $attempt ) {
            return '<div class="uesp-result uesp-glass"><h3>' . esc_html__( 'Result not found.', 'uesp' ) . '</h3></div>';
        }

        if ( $attempt->user_id && (int) $attempt->user_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            return '<div class="uesp-result uesp-glass"><h3>' . esc_html__( 'You are not allowed to view this result.', 'uesp' ) . '</h3></div>';
        }

        wp_enqueue_style( 'uesp-frontend' );

        $answer_count  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tables['answers']} WHERE attempt_id = %d", $attempt_id ) );
        $correct_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tables['answers']} WHERE attempt_id = %d AND is_correct = 1", $attempt_id ) );

        ob_start();
        ?>
        <div class="uesp-result uesp-glass">
            <p class="uesp-result-kicker"><?php esc_html_e( 'Exam Result', 'uesp' ); ?></p>
            <h2><?php esc_html_e( 'Your exam has been submitted successfully.', 'uesp' ); ?></h2>
            <div class="uesp-result-grid">
                <div>
                    <span><?php esc_html_e( 'Attempt ID', 'uesp' ); ?></span>
                    <strong><?php echo esc_html( $attempt_id ); ?></strong>
                </div>
                <div>
                    <span><?php esc_html_e( 'Total Score', 'uesp' ); ?></span>
                    <strong><?php echo esc_html( (int) $attempt->total_score ); ?></strong>
                </div>
                <div>
                    <span><?php esc_html_e( 'Correct Answers', 'uesp' ); ?></span>
                    <strong><?php echo esc_html( $correct_count . '/' . $answer_count ); ?></strong>
                </div>
                <div>
                    <span><?php esc_html_e( 'Submitted At', 'uesp' ); ?></span>
                    <strong><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $attempt->created_at ) ); ?></strong>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
