-- Email Management System Tables
-- Add this to your existing database

-- Emails table to track all sent and received emails
CREATE TABLE IF NOT EXISTS `emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` varchar(255) DEFAULT NULL COMMENT 'Email Message-ID header',
  `in_reply_to` varchar(255) DEFAULT NULL COMMENT 'In-Reply-To header for threading',
  `reference_type` enum('quotation','invoice','general') NOT NULL DEFAULT 'general',
  `reference_id` int(11) DEFAULT NULL COMMENT 'ID of quotation or invoice',
  `direction` enum('outgoing','incoming') NOT NULL DEFAULT 'outgoing',
  `from_email` varchar(255) NOT NULL,
  `from_name` varchar(255) DEFAULT NULL,
  `to_email` text NOT NULL COMMENT 'Primary recipient(s), comma-separated',
  `cc_email` text DEFAULT NULL COMMENT 'CC recipients, comma-separated',
  `bcc_email` text DEFAULT NULL COMMENT 'BCC recipients, comma-separated',
  `subject` varchar(500) NOT NULL,
  `body_html` longtext DEFAULT NULL,
  `body_text` text DEFAULT NULL,
  `has_attachment` tinyint(1) DEFAULT 0,
  `attachment_name` varchar(255) DEFAULT NULL,
  `status` enum('sent','failed','received','read') DEFAULT 'sent',
  `error_message` text DEFAULT NULL,
  `sent_by` int(11) DEFAULT NULL COMMENT 'User ID who sent the email',
  `sent_at` datetime NOT NULL,
  `received_at` datetime DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_message_id` (`message_id`),
  KEY `idx_reference` (`reference_type`,`reference_id`),
  KEY `idx_direction` (`direction`),
  KEY `idx_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email attachments table (for incoming emails with attachments)
CREATE TABLE IF NOT EXISTS `email_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(500) NOT NULL,
  `filesize` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email_id` (`email_id`),
  CONSTRAINT `fk_email_attachments_email` FOREIGN KEY (`email_id`) REFERENCES `emails` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add IMAP configuration to system settings (optional - can also use config.php)
CREATE TABLE IF NOT EXISTS `email_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default IMAP settings
INSERT INTO `email_settings` (`setting_key`, `setting_value`) VALUES
('imap_enabled', '0'),
('imap_host', ''),
('imap_port', '993'),
('imap_username', ''),
('imap_password', ''),
('imap_ssl', '1'),
('last_sync', NULL)
ON DUPLICATE KEY UPDATE setting_key=setting_key;

