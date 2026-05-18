<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class UESP_Admin {
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_post_uesp_save_question', array( __CLASS__, 'save_question' ) );
        add_action( 'admin_post_uesp_delete_question', array( __CLASS__, 'delete_question' ) );
    }

    public static function menu() {
        add_menu_page(
            __( 'UESP Exams', 'uesp' ),
            __( 'UESP Exams', 'uesp' ),
            'manage_options',
            'uesp-exams',
            array( __CLASS__, 'questions_page' ),
            'dashicons-welcome-learn-more',
            26
        );
    }

    public static function questions_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to access this page.', 'uesp' ) );
        }

        global $wpdb;
        $tables    = UESP_DB::tables();
        $questions = $wpdb->get_results( "SELECT * FROM {$tables['questions']} ORDER BY id DESC LIMIT 100" );
        ?>
        <div class="wrap uesp-admin-wrap">
            <h1><?php esc_html_e( 'UESP Exam Questions', 'uesp' ); ?></h1>

            <?php if ( isset( $_GET['uesp_saved'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Question saved.', 'uesp' ); ?></p></div>
            <?php endif; ?>
            <?php if ( isset( $_GET['uesp_deleted'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Question deleted.', 'uesp' ); ?></p></div>
            <?php endif; ?>

            <div class="uesp-admin-grid">
                <form class="uesp-admin-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <h2><?php esc_html_e( 'Add Question', 'uesp' ); ?></h2>
                    <?php wp_nonce_field( 'uesp_save_question' ); ?>
                    <input type="hidden" name="action" value="uesp_save_question">

                    <label>
                        <?php esc_html_e( 'Question Text', 'uesp' ); ?>
                        <textarea name="question_text" rows="5" required></textarea>
                    </label>

                    <div class="uesp-admin-two-col">
                        <?php for ( $i = 1; $i <= 4; $i++ ) : ?>
                            <label>
                                <?php echo esc_html( sprintf( __( 'Option %d', 'uesp' ), $i ) ); ?>
                                <input type="text" name="option<?php echo esc_attr( $i ); ?>" required>
                            </label>
                        <?php endfor; ?>
                    </div>

                    <div class="uesp-admin-two-col">
                        <label>
                            <?php esc_html_e( 'Correct Answer', 'uesp' ); ?>
                            <select name="correct_answer" required>
                                <option value="1">A / Option 1</option>
                                <option value="2">B / Option 2</option>
                                <option value="3">C / Option 3</option>
                                <option value="4">D / Option 4</option>
                            </select>
                        </label>
                        <label>
                            <?php esc_html_e( 'Marks', 'uesp' ); ?>
                            <input type="number" name="marks" min="1" value="1" required>
                        </label>
                    </div>

                    <label>
                        <?php esc_html_e( 'Category', 'uesp' ); ?>
                        <input type="text" name="category" placeholder="<?php esc_attr_e( 'General Knowledge', 'uesp' ); ?>">
                    </label>

                    <?php submit_button( __( 'Save Question', 'uesp' ) ); ?>
                </form>

                <div class="uesp-admin-card">
                    <h2><?php esc_html_e( 'Shortcodes', 'uesp' ); ?></h2>
                    <p><code>[uesp_exam]</code> <?php esc_html_e( 'renders the exam interface.', 'uesp' ); ?></p>
                    <p><code>[uesp_exam_result]</code> <?php esc_html_e( 'renders the result page at /exam-result/.', 'uesp' ); ?></p>
                    <p><?php esc_html_e( 'Create a page with slug exam-result and place the result shortcode on that page.', 'uesp' ); ?></p>
                </div>
            </div>

            <h2><?php esc_html_e( 'Recent Questions', 'uesp' ); ?></h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'ID', 'uesp' ); ?></th>
                        <th><?php esc_html_e( 'Question', 'uesp' ); ?></th>
                        <th><?php esc_html_e( 'Category', 'uesp' ); ?></th>
                        <th><?php esc_html_e( 'Marks', 'uesp' ); ?></th>
                        <th><?php esc_html_e( 'Correct', 'uesp' ); ?></th>
                        <th><?php esc_html_e( 'Action', 'uesp' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $questions ) : ?>
                        <?php foreach ( $questions as $question ) : ?>
                            <tr>
                                <td><?php echo esc_html( (int) $question->id ); ?></td>
                                <td><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $question->question_text ), 18 ) ); ?></td>
                                <td><?php echo esc_html( $question->category ); ?></td>
                                <td><?php echo esc_html( (int) $question->marks ); ?></td>
                                <td><?php echo esc_html( $question->correct_answer ); ?></td>
                                <td>
                                    <a class="button button-link-delete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=uesp_delete_question&id=' . absint( $question->id ) ), 'uesp_delete_question_' . absint( $question->id ) ) ); ?>">
                                        <?php esc_html_e( 'Delete', 'uesp' ); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="6"><?php esc_html_e( 'No questions have been added yet.', 'uesp' ); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function save_question() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to do this.', 'uesp' ) );
        }

        check_admin_referer( 'uesp_save_question' );

        global $wpdb;
        $tables = UESP_DB::tables();

        $question_text  = isset( $_POST['question_text'] ) ? wp_kses_post( wp_unslash( $_POST['question_text'] ) ) : '';
        $option1        = isset( $_POST['option1'] ) ? sanitize_text_field( wp_unslash( $_POST['option1'] ) ) : '';
        $option2        = isset( $_POST['option2'] ) ? sanitize_text_field( wp_unslash( $_POST['option2'] ) ) : '';
        $option3        = isset( $_POST['option3'] ) ? sanitize_text_field( wp_unslash( $_POST['option3'] ) ) : '';
        $option4        = isset( $_POST['option4'] ) ? sanitize_text_field( wp_unslash( $_POST['option4'] ) ) : '';
        $correct_answer = isset( $_POST['correct_answer'] ) ? sanitize_text_field( wp_unslash( $_POST['correct_answer'] ) ) : '1';
        $marks          = isset( $_POST['marks'] ) ? max( 1, absint( $_POST['marks'] ) ) : 1;
        $category       = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : '';

        if ( '' === $question_text || '' === $option1 || '' === $option2 || '' === $option3 || '' === $option4 || ! in_array( $correct_answer, array( '1', '2', '3', '4' ), true ) ) {
            wp_die( esc_html__( 'Please provide a valid question, four options, and a correct answer.', 'uesp' ) );
        }

        $wpdb->insert(
            $tables['questions'],
            array(
                'question_text'  => $question_text,
                'option1'        => $option1,
                'option2'        => $option2,
                'option3'        => $option3,
                'option4'        => $option4,
                'correct_answer' => $correct_answer,
                'marks'          => $marks,
                'category'       => $category,
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
        );

        wp_safe_redirect( add_query_arg( 'uesp_saved', '1', admin_url( 'admin.php?page=uesp-exams' ) ) );
        exit;
    }

    public static function delete_question() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not allowed to do this.', 'uesp' ) );
        }

        $id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        if ( ! $id ) {
            wp_die( esc_html__( 'Invalid question.', 'uesp' ) );
        }

        check_admin_referer( 'uesp_delete_question_' . $id );

        global $wpdb;
        $tables = UESP_DB::tables();
        $wpdb->delete( $tables['questions'], array( 'id' => $id ), array( '%d' ) );

        wp_safe_redirect( add_query_arg( 'uesp_deleted', '1', admin_url( 'admin.php?page=uesp-exams' ) ) );
        exit;
    }
}
