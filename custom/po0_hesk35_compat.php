<?php
/**
 * HESK 3.5.0 Compatibility Layer
 * 
 * Provides missing functions for HESK 3.5.0 that don't exist in the base install
 * but are expected by core files (e.g., customer account functions).
 * 
 * These are stub implementations to prevent fatal errors and provide
 * basic functionality where the full HESK 3.5.0 feature set isn't available.
 * 
 * @package HESK-Custom
 * @since 3.5.0
 */

if (!defined('IN_SCRIPT')) {
    die('Invalid attempt');
}

/**
 * Check if a ticket is bookmarked by a staff member
 * 
 * @param int $ticket_id Ticket ID
 * @param int $staff_id Staff member ID (optional)
 * @return bool Always returns false (bookmarking not implemented)
 */
function hesk_isTicketBookmarked($ticket_id = 0, $staff_id = 0) {
    // Bookmarking feature not implemented
    // Return false by default (ticket not bookmarked)
    return false;
}

/**
 * Get customer account information by email
 * 
 * @param string $email Customer email address
 * @return array|false Customer data or false if not found
 */
function hesk_getCustomerAccount($email) {
    // Customer accounts feature not fully implemented
    // Return false (no customer account)
    return false;
}

/**
 * Check if customer accounts feature is enabled
 * 
 * @return bool True if enabled, false otherwise
 */
function hesk_customerAccountsEnabled() {
    global $hesk_settings;
    // Return false by default unless explicitly enabled in settings
    return isset($hesk_settings['customer_accounts']) && $hesk_settings['customer_accounts'];
}

/**
 * Get all customers associated with a ticket
 * 
 * Retrieves customers from hesk_customers and hesk_ticket_to_customer tables.
 * Returns array of customers with their type (REQUESTER, CC, FOLLOWER).
 * 
 * @param int $ticket_id Ticket ID
 * @return array Array of customer records with id, name, email, customer_type
 */
function hesk_get_customers_for_ticket($ticket_id) {
    global $hesk_settings;
    
    $customers = array();
    
    // Query to get all customers linked to this ticket
    $res = hesk_dbQuery("
        SELECT 
            c.id,
            c.name,
            c.email,
            ttc.customer_type
        FROM `" . hesk_dbEscape($hesk_settings['db_pfix']) . "ticket_to_customer` ttc
        INNER JOIN `" . hesk_dbEscape($hesk_settings['db_pfix']) . "customers` c ON ttc.customer_id = c.id
        WHERE ttc.ticket_id = " . intval($ticket_id) . "
    ");
    
    // Fetch all customer records
    while ($row = hesk_dbFetchAssoc($res)) {
        $customers[] = $row;
    }
    
    return $customers;
}

/**
 * Generate a modal ID for delete confirmation dialogs
 * 
 * This is a simplified version that just returns a unique modal ID.
 * The actual modal HTML generation is handled by HESK core.
 * 
 * @param string $title Modal title
 * @param string $message Modal message/description
 * @param string $url Action URL for the delete operation
 * @param string $confirm_text Confirmation button text (default: 'Confirm')
 * @return string Unique modal ID
 */
function hesk_generate_old_delete_modal($title, $message, $url, $confirm_text = 'Confirm') {
    // Return a unique modal ID based on the URL
    // The actual modal rendering is handled by HESK's JavaScript
    return 'modal_' . md5($url);
}

