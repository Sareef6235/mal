-- Realtime notification + chat upgrade SQL for the existing support dashboard.
-- Intended for MySQL 8+ / MariaDB environments.
-- Run after your base tables already exist.

START TRANSACTION;

-- -----------------------------------------------------------------------------
-- 1) Upgrade ticket_messages for unread/seen tracking and better chat metadata.
-- -----------------------------------------------------------------------------
ALTER TABLE ticket_messages
    ADD COLUMN IF NOT EXISTS sender_role ENUM('user', 'admin') NOT NULL DEFAULT 'user' AFTER source,
    ADD COLUMN IF NOT EXISTS is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER sender_role,
    ADD COLUMN IF NOT EXISTS delivered_at TIMESTAMP NULL DEFAULT NULL AFTER is_read,
    ADD COLUMN IF NOT EXISTS seen_at TIMESTAMP NULL DEFAULT NULL AFTER delivered_at,
    ADD COLUMN IF NOT EXISTS seen_by_admin_at TIMESTAMP NULL DEFAULT NULL AFTER seen_at,
    ADD COLUMN IF NOT EXISTS seen_by_customer_at TIMESTAMP NULL DEFAULT NULL AFTER seen_by_admin_at,
    ADD COLUMN IF NOT EXISTS delivered_to_admin_at TIMESTAMP NULL DEFAULT NULL AFTER seen_by_customer_at,
    ADD COLUMN IF NOT EXISTS delivered_to_customer_at TIMESTAMP NULL DEFAULT NULL AFTER delivered_to_admin_at,
    ADD COLUMN IF NOT EXISTS message_type VARCHAR(30) NOT NULL DEFAULT 'text' AFTER seen_at,
    ADD COLUMN IF NOT EXISTS edited_at TIMESTAMP NULL DEFAULT NULL AFTER message_type;

ALTER TABLE ticket_messages
    ADD INDEX IF NOT EXISTS idx_ticket_messages_ticket_read (ticket_id, is_read, sender_role, created_at),
    ADD INDEX IF NOT EXISTS idx_ticket_messages_ticket_seen (ticket_id, seen_at),
    ADD INDEX IF NOT EXISTS idx_ticket_messages_created (created_at);

-- -----------------------------------------------------------------------------
-- 2) Upgrade compatibility `messages` table if your project still uses it.
-- -----------------------------------------------------------------------------
ALTER TABLE messages
    ADD COLUMN IF NOT EXISTS sender_role ENUM('user', 'admin') NOT NULL DEFAULT 'user' AFTER message,
    ADD COLUMN IF NOT EXISTS is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER sender_role,
    ADD COLUMN IF NOT EXISTS delivered_at TIMESTAMP NULL DEFAULT NULL AFTER is_read,
    ADD COLUMN IF NOT EXISTS seen_at TIMESTAMP NULL DEFAULT NULL AFTER delivered_at,
    ADD COLUMN IF NOT EXISTS seen_by_admin_at TIMESTAMP NULL DEFAULT NULL AFTER seen_at,
    ADD COLUMN IF NOT EXISTS seen_by_customer_at TIMESTAMP NULL DEFAULT NULL AFTER seen_by_admin_at,
    ADD COLUMN IF NOT EXISTS delivered_to_admin_at TIMESTAMP NULL DEFAULT NULL AFTER seen_by_customer_at,
    ADD COLUMN IF NOT EXISTS delivered_to_customer_at TIMESTAMP NULL DEFAULT NULL AFTER delivered_to_admin_at,
    ADD COLUMN IF NOT EXISTS message_type VARCHAR(30) NOT NULL DEFAULT 'text' AFTER seen_at,
    ADD COLUMN IF NOT EXISTS edited_at TIMESTAMP NULL DEFAULT NULL AFTER message_type;

ALTER TABLE messages
    ADD INDEX IF NOT EXISTS idx_messages_ticket_read (ticket_id, is_read, sender_role, created_at),
    ADD INDEX IF NOT EXISTS idx_messages_ticket_seen (ticket_id, seen_at),
    ADD INDEX IF NOT EXISTS idx_messages_created (created_at);

-- -----------------------------------------------------------------------------
-- 3) Add helpful ticket-level realtime columns.
-- -----------------------------------------------------------------------------
ALTER TABLE tickets
    ADD COLUMN IF NOT EXISTS last_message_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at,
    ADD COLUMN IF NOT EXISTS last_message_preview VARCHAR(255) NULL AFTER last_message_at,
    ADD COLUMN IF NOT EXISTS admin_unread_count INT NOT NULL DEFAULT 0 AFTER last_message_preview,
    ADD COLUMN IF NOT EXISTS customer_unread_count INT NOT NULL DEFAULT 0 AFTER admin_unread_count,
    ADD COLUMN IF NOT EXISTS typing_role ENUM('user', 'admin') NULL DEFAULT NULL AFTER customer_unread_count,
    ADD COLUMN IF NOT EXISTS typing_updated_at TIMESTAMP NULL DEFAULT NULL AFTER typing_role,
    ADD COLUMN IF NOT EXISTS admin_last_active_at TIMESTAMP NULL DEFAULT NULL AFTER typing_updated_at,
    ADD COLUMN IF NOT EXISTS customer_last_active_at TIMESTAMP NULL DEFAULT NULL AFTER admin_last_active_at;

ALTER TABLE tickets
    ADD INDEX IF NOT EXISTS idx_tickets_last_message_at (last_message_at),
    ADD INDEX IF NOT EXISTS idx_tickets_admin_unread (admin_unread_count),
    ADD INDEX IF NOT EXISTS idx_tickets_customer_unread (customer_unread_count);

-- -----------------------------------------------------------------------------
-- 4) Upgrade order_messages so the existing notification section can track status.
-- -----------------------------------------------------------------------------
ALTER TABLE order_messages
    ADD COLUMN IF NOT EXISTS is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER admin_reply,
    ADD COLUMN IF NOT EXISTS delivered_at TIMESTAMP NULL DEFAULT NULL AFTER is_read,
    ADD COLUMN IF NOT EXISTS seen_at TIMESTAMP NULL DEFAULT NULL AFTER delivered_at,
    ADD COLUMN IF NOT EXISTS seen_by_admin_at TIMESTAMP NULL DEFAULT NULL AFTER seen_at,
    ADD COLUMN IF NOT EXISTS seen_by_customer_at TIMESTAMP NULL DEFAULT NULL AFTER seen_by_admin_at,
    ADD COLUMN IF NOT EXISTS delivered_to_admin_at TIMESTAMP NULL DEFAULT NULL AFTER seen_by_customer_at,
    ADD COLUMN IF NOT EXISTS delivered_to_customer_at TIMESTAMP NULL DEFAULT NULL AFTER delivered_to_admin_at,
    ADD COLUMN IF NOT EXISTS notification_sent_at TIMESTAMP NULL DEFAULT NULL AFTER seen_at;

ALTER TABLE order_messages
    ADD INDEX IF NOT EXISTS idx_order_messages_read (is_read, created_at),
    ADD INDEX IF NOT EXISTS idx_order_messages_seen (seen_at),
    ADD INDEX IF NOT EXISTS idx_order_messages_notification_sent (notification_sent_at);

-- -----------------------------------------------------------------------------
-- 5) Optional push-notification support.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS push_subscriptions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    endpoint VARCHAR(500) NOT NULL,
    p256dh_key VARCHAR(255) NOT NULL,
    auth_key VARCHAR(255) NOT NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_push_subscriptions_endpoint (endpoint),
    KEY idx_push_subscriptions_user_id (user_id),
    CONSTRAINT fk_push_subscriptions_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6) Optional notification event log for sound/push/websocket/audit flows.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notification_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id BIGINT UNSIGNED NULL,
    message_id BIGINT UNSIGNED NULL,
    event_type VARCHAR(50) NOT NULL,
    channel VARCHAR(30) NOT NULL DEFAULT 'system',
    payload JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notification_events_ticket (ticket_id, created_at),
    KEY idx_notification_events_message (message_id),
    KEY idx_notification_events_type (event_type, created_at),
    CONSTRAINT fk_notification_events_ticket
        FOREIGN KEY (ticket_id) REFERENCES tickets (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- 7) Optional retry queue + online session tracking for WhatsApp-like delivery UX.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS chat_retry_queue (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    message_text TEXT NOT NULL,
    retry_count INT NOT NULL DEFAULT 0,
    status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    last_error VARCHAR(255) NULL,
    next_retry_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_chat_retry_status (status, next_retry_at),
    KEY idx_chat_retry_ticket (ticket_id, created_at),
    CONSTRAINT fk_chat_retry_ticket
        FOREIGN KEY (ticket_id) REFERENCES tickets (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_presence_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    session_key VARCHAR(120) NOT NULL,
    last_seen_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_chat_presence_session (session_key),
    KEY idx_chat_presence_ticket_role (ticket_id, role, last_seen_at),
    CONSTRAINT fk_chat_presence_ticket
        FOREIGN KEY (ticket_id) REFERENCES tickets (id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
