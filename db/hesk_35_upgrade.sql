-- HESK 3.5.0+ Database Schema Updates
-- Customer accounts and ticket-to-customer relationship tables

CREATE TABLE IF NOT EXISTS `hesk_customers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `verified` enum('0','1') NOT NULL DEFAULT '0',
  `verification_token` varchar(64) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `verification_token` (`verification_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hesk_ticket_to_customer` (
  `ticket_id` mediumint unsigned NOT NULL,
  `customer_id` int unsigned NOT NULL,
  `customer_type` enum('REQUESTER','CC') NOT NULL DEFAULT 'REQUESTER',
  PRIMARY KEY (`ticket_id`,`customer_id`,`customer_type`),
  KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

