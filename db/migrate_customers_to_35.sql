-- Migrate existing ticket customer data to HESK 3.5+ customer tables

-- First, create customer records from unique ticket submitters
INSERT IGNORE INTO `hesk_customers` (`email`, `name`, `verified`, `created_at`)
SELECT DISTINCT 
    `email`,
    `name`,
    '1' as verified,
    `dt` as created_at
FROM `hesk_tickets`
WHERE `email` IS NOT NULL AND `email` != '';

-- Then link tickets to their customers
INSERT IGNORE INTO `hesk_ticket_to_customer` (`ticket_id`, `customer_id`, `customer_type`)
SELECT 
    t.`id` as ticket_id,
    c.`id` as customer_id,
    'REQUESTER' as customer_type
FROM `hesk_tickets` t
INNER JOIN `hesk_customers` c ON c.`email` = t.`email`
WHERE t.`email` IS NOT NULL AND t.`email` != '';

