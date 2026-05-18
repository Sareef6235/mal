<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class UESP_DB {
    /**
     * Get plugin table names.
     *
     * @return array<string,string>
     */
    public static function tables() {
        global $wpdb;

        return array(
            'questions'       => $wpdb->prefix . 'exam_questions',
            'attempts'        => $wpdb->prefix . 'exam_attempts',
            'answers'         => $wpdb->prefix . 'exam_answers',
            'suspicious_logs' => $wpdb->prefix . 'exam_suspicious_logs',
        );
    }

    /**
     * Create database tables on activation.
     */
    public static function activate() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $tables          = self::tables();

        $sql_questions = "CREATE TABLE {$tables['questions']} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            question_text LONGTEXT NOT NULL,
            option1 TEXT NOT NULL,
            option2 TEXT NOT NULL,
            option3 TEXT NOT NULL,
            option4 TEXT NOT NULL,
            correct_answer VARCHAR(20) NOT NULL,
            marks INT(11) NOT NULL DEFAULT 1,
            category VARCHAR(191) NOT NULL DEFAULT '',
            PRIMARY KEY (id),
            KEY category (category)
        ) {$charset_collate};";

        $sql_attempts = "CREATE TABLE {$tables['attempts']} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            total_score INT(11) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        $sql_answers = "CREATE TABLE {$tables['answers']} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            attempt_id BIGINT(20) UNSIGNED NOT NULL,
            question_id BIGINT(20) UNSIGNED NOT NULL,
            answer VARCHAR(20) NOT NULL DEFAULT '',
            is_correct TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY attempt_id (attempt_id),
            KEY question_id (question_id)
        ) {$charset_collate};";

        $sql_logs = "CREATE TABLE {$tables['suspicious_logs']} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            event_type VARCHAR(100) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) {$charset_collate};";

        dbDelta( $sql_questions );
        dbDelta( $sql_attempts );
        dbDelta( $sql_answers );
        dbDelta( $sql_logs );
    }
}
