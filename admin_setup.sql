-- SQL setup for admin.php ticket dashboard and order message notifications.
-- Safe to run on a MySQL / MariaDB database.

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    phone VARCHAR(40) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tickets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_code VARCHAR(50) NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    subject VARCHAR(255) NOT NULL,
    order_reference VARCHAR(120) NULL,
    category VARCHAR(120) NOT NULL DEFAULT 'General Support',
    priority ENUM('Low', 'Normal', 'High', 'Urgent') NOT NULL DEFAULT 'Normal',
    status ENUM('Open', 'Pending', 'Resolved') NOT NULL DEFAULT 'Open',
    typing_role ENUM('user', 'admin') NULL DEFAULT NULL,
    typing_updated_at TIMESTAMP NULL DEFAULT NULL,
    admin_last_active_at TIMESTAMP NULL DEFAULT NULL,
    customer_last_active_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tickets_ticket_code (ticket_code),
    KEY idx_tickets_user_id (user_id),
    KEY idx_tickets_status_created (status, created_at),
    KEY idx_tickets_priority (priority),
    CONSTRAINT fk_tickets_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ticket_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    message TEXT NOT NULL,
    source VARCHAR(40) NOT NULL DEFAULT 'web',
    sender_role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    delivered_at TIMESTAMP NULL DEFAULT NULL,
    seen_at TIMESTAMP NULL DEFAULT NULL,
    seen_by_admin_at TIMESTAMP NULL DEFAULT NULL,
    seen_by_customer_at TIMESTAMP NULL DEFAULT NULL,
    delivered_to_admin_at TIMESTAMP NULL DEFAULT NULL,
    delivered_to_customer_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ticket_messages_ticket_created (ticket_id, created_at),
    KEY idx_ticket_messages_user_id (user_id),
    KEY idx_ticket_messages_unread (ticket_id, is_read, sender_role, created_at),
    CONSTRAINT fk_ticket_messages_ticket
        FOREIGN KEY (ticket_id) REFERENCES tickets (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_ticket_messages_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id BIGINT UNSIGNED NOT NULL,
    channel ENUM('email', 'whatsapp', 'sms', 'system') NOT NULL DEFAULT 'system',
    recipient VARCHAR(255) NOT NULL,
    payload JSON NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'queued',
    response TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notifications_ticket_id (ticket_id),
    KEY idx_notifications_channel_status (channel, status),
    CONSTRAINT fk_notifications_ticket
        FOREIGN KEY (ticket_id) REFERENCES tickets (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_name VARCHAR(190) NOT NULL,
    class_value VARCHAR(80) NOT NULL,
    gender VARCHAR(30) NOT NULL,
    message_text TEXT NOT NULL,
    admin_reply TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    replied_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_order_messages_created (created_at),
    KEY idx_order_messages_student (student_name),
    KEY idx_order_messages_pending (replied_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional compatibility table if some older code writes into `messages` instead of `ticket_messages`.
CREATE TABLE IF NOT EXISTS messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    message TEXT NOT NULL,
    sender_role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    seen_at TIMESTAMP NULL DEFAULT NULL,
    seen_by_admin_at TIMESTAMP NULL DEFAULT NULL,
    seen_by_customer_at TIMESTAMP NULL DEFAULT NULL,
    delivered_to_admin_at TIMESTAMP NULL DEFAULT NULL,
    delivered_to_customer_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_messages_ticket_created (ticket_id, created_at),
    KEY idx_messages_user_id (user_id),
    KEY idx_messages_unread (ticket_id, is_read, sender_role, created_at),
    CONSTRAINT fk_messages_ticket
        FOREIGN KEY (ticket_id) REFERENCES tickets (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_messages_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE ticket_messages
    ADD COLUMN IF NOT EXISTS sender_role ENUM('user', 'admin') NOT NULL DEFAULT 'user' AFTER source,
    ADD COLUMN IF NOT EXISTS is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER sender_role,
    ADD COLUMN IF NOT EXISTS delivered_at TIMESTAMP NULL DEFAULT NULL AFTER is_read,
    ADD COLUMN IF NOT EXISTS seen_at TIMESTAMP NULL DEFAULT NULL AFTER delivered_at,
    ADD COLUMN IF NOT EXISTS seen_by_admin_at TIMESTAMP NULL DEFAULT NULL AFTER seen_at,
    ADD COLUMN IF NOT EXISTS seen_by_customer_at TIMESTAMP NULL DEFAULT NULL AFTER seen_by_admin_at,
    ADD COLUMN IF NOT EXISTS delivered_to_admin_at TIMESTAMP NULL DEFAULT NULL AFTER seen_by_customer_at,
    ADD COLUMN IF NOT EXISTS delivered_to_customer_at TIMESTAMP NULL DEFAULT NULL AFTER delivered_to_admin_at,
    ADD INDEX IF NOT EXISTS idx_ticket_messages_unread (ticket_id, is_read, sender_role, created_at);

ALTER TABLE messages
    ADD COLUMN IF NOT EXISTS sender_role ENUM('user', 'admin') NOT NULL DEFAULT 'user' AFTER message,
    ADD COLUMN IF NOT EXISTS is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER sender_role,
    ADD COLUMN IF NOT EXISTS seen_at TIMESTAMP NULL DEFAULT NULL AFTER is_read,
    ADD COLUMN IF NOT EXISTS seen_by_admin_at TIMESTAMP NULL DEFAULT NULL AFTER seen_at,
    ADD COLUMN IF NOT EXISTS seen_by_customer_at TIMESTAMP NULL DEFAULT NULL AFTER seen_by_admin_at,
    ADD COLUMN IF NOT EXISTS delivered_to_admin_at TIMESTAMP NULL DEFAULT NULL AFTER seen_by_customer_at,
    ADD COLUMN IF NOT EXISTS delivered_to_customer_at TIMESTAMP NULL DEFAULT NULL AFTER delivered_to_admin_at,
    ADD INDEX IF NOT EXISTS idx_messages_unread (ticket_id, is_read, sender_role, created_at);
