<?php

if (!defined('IN_SCRIPT')) {
    return;
}

// Only run on admin and ticket-related pages to reduce overhead
$script = isset($_SERVER['SCRIPT_NAME']) ? basename($_SERVER['SCRIPT_NAME']) : '';
// Basic log to help diagnose blank pages
if (function_exists('error_log')) {
    error_log("[HESK-CUSTOM] bootstrap on {$script} staff_id=" . (isset($_SESSION['id'])?$_SESSION['id']:'-'));
}
$interesting = array(
    'admin_main.php','show_tickets.php','find_tickets.php','admin_ticket.php',
    'admin_reply_ticket.php','reply_ticket.php','submit_ticket.php',
    'save_ticket_draft_async.php','change_status.php','priority.php',
);
if ( ! in_array($script, $interesting, true)) {
    // Still set session variable for any request that connects to DB
    add_session_lastchange_var();
    return;
}

// Always set current staff id into MySQL session for triggers that use @HESK_LASTCHANGE_BY (attachments, etc.)
try {
    add_session_lastchange_var();
} catch (Exception $e) {
    error_log("[HESK-CUSTOM] Failed to set session var: " . $e->getMessage());
}

// Ensure table and triggers exist (idempotent)
try {
    if (!function_exists('hesk_dbQuery')) {
        error_log("[HESK-CUSTOM] hesk_dbQuery not available, skipping DB setup");
        return;
    }
    ensure_hesk_ticket_updates_table();
    ensure_hesk_updates_triggers();
} catch (Exception $e) {
    error_log("[HESK-CUSTOM] DB bootstrap failed: " . $e->getMessage());
    error_log("[HESK-CUSTOM] Stack trace: " . $e->getTraceAsString());
}

function add_session_lastchange_var()
{
    if (!function_exists('hesk_dbQuery')) {
        return;
    }
    $staff_id = (isset($_SESSION['id']) && is_numeric($_SESSION['id'])) ? (int) $_SESSION['id'] : 0;
    // Fallback in case $_SESSION isn't started yet
    if (!isset($_SESSION['id'])) {
        $staff_id = 0;
    }
    $val = $staff_id > 0 ? (int)$staff_id : 'NULL';
    @hesk_dbQuery('SET @HESK_LASTCHANGE_BY = ' . $val);
}

function ensure_hesk_ticket_updates_table()
{
    global $hesk_settings;
    $tbl = 'hesk_ticket_updates';
    
    try {
        $res = @hesk_dbQuery("SHOW TABLES LIKE '{$tbl}'");
        if ($res && hesk_dbNumRows($res) > 0) {
            error_log("[HESK-CUSTOM] Table {$tbl} already exists");
            return;
        }
        
        error_log("[HESK-CUSTOM] Creating table {$tbl}");
        $sql = "
        CREATE TABLE IF NOT EXISTS `{$tbl}` (
          `ticket_id` MEDIUMINT UNSIGNED NOT NULL,
          `staff_id`  MEDIUMINT UNSIGNED NOT NULL,
          `changed_at` DATETIME NOT NULL,
          PRIMARY KEY (`ticket_id`,`staff_id`),
          KEY `by_staff` (`staff_id`,`changed_at`),
          KEY `by_changed_at` (`changed_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ";
        $result = @hesk_dbQuery($sql);
        if ($result === false) {
            error_log("[HESK-CUSTOM] Failed to create table {$tbl}");
        } else {
            error_log("[HESK-CUSTOM] Table {$tbl} created successfully");
        }
    } catch (Exception $e) {
        error_log("[HESK-CUSTOM] Exception in ensure_hesk_ticket_updates_table: " . $e->getMessage());
        throw $e;
    }
}

function ensure_hesk_updates_triggers()
{
    try {
        // Check for a specific trigger we need; if present, assume all are installed
        $check = @hesk_dbQuery("SHOW TRIGGERS WHERE `Trigger` = 'hesk_tickets_updates_au'");
        if ($check && hesk_dbNumRows($check) > 0) {
            error_log("[HESK-CUSTOM] Triggers already exist");
            return;
        }

        error_log("[HESK-CUSTOM] Installing triggers");
        
        // Load triggers from SQL file (safer than inline SQL with DELIMITER issues)
        $sqlFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'triggers.sql';
        if (!file_exists($sqlFile)) {
            error_log("[HESK-CUSTOM] triggers.sql not found at {$sqlFile}");
            return;
        }

        $ddl = file_get_contents($sqlFile);
        if ($ddl === false) {
            error_log("[HESK-CUSTOM] Failed to read triggers.sql");
            return;
        }

        // Remove DELIMITER statements and split by // (the actual delimiter in the file)
        $ddl = preg_replace('/^\s*DELIMITER\s+.*$/mi', '', $ddl);
        $statements = preg_split('/\s*\/\/\s*/', $ddl, -1, PREG_SPLIT_NO_EMPTY);
        
        $count = 0;
        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '' || strpos($stmt, 'DROP TRIGGER') === false && strpos($stmt, 'CREATE TRIGGER') === false) {
                continue;
            }
            $result = @hesk_dbQuery($stmt);
            if ($result === false) {
                error_log("[HESK-CUSTOM] Failed to execute trigger statement: " . substr($stmt, 0, 100));
            } else {
                $count++;
            }
        }
        
        error_log("[HESK-CUSTOM] Installed {$count} trigger statements");
        return;
    } catch (Exception $e) {
        error_log("[HESK-CUSTOM] Exception in ensure_hesk_updates_triggers: " . $e->getMessage());
        throw $e;
    }
    
    // OLD INLINE CODE (kept for reference, but using file-based approach above)
    $ddl_old = <<<SQL
    DROP TRIGGER IF EXISTS `hesk_tickets_updates_au`;
    CREATE TRIGGER `hesk_tickets_updates_au`
    AFTER UPDATE ON `hesk_tickets` FOR EACH ROW
    BEGIN
      INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
      VALUES (NEW.`id`,0,NOW())
      ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
      IF COALESCE(NEW.`lastchange_by`,0) > 0 THEN
        INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
        VALUES (NEW.`id`,NEW.`lastchange_by`,NOW())
        ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
      END IF;
    END;

    DROP TRIGGER IF EXISTS `hesk_tickets_insert_ai`;
    CREATE TRIGGER `hesk_tickets_insert_ai`
    AFTER INSERT ON `hesk_tickets` FOR EACH ROW
    BEGIN
      INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
      VALUES (NEW.`id`,0,NOW())
      ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
      IF COALESCE(NEW.`lastchange_by`,0) > 0 THEN
        INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
        VALUES (NEW.`id`,NEW.`lastchange_by`,NOW())
        ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
      END IF;
    END;

    DROP TRIGGER IF EXISTS `hesk_replies_updates_ai`;
    CREATE TRIGGER `hesk_replies_updates_ai`
    AFTER INSERT ON `hesk_replies` FOR EACH ROW
    BEGIN
      INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
      VALUES (NEW.`replyto`,0,NOW())
      ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
      IF COALESCE(NEW.`staffid`,0) > 0 THEN
        INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
        VALUES (NEW.`replyto`,NEW.`staffid`,NOW())
        ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
      END IF;
    END;

    DROP TRIGGER IF EXISTS `hesk_replies_update_au`;
    CREATE TRIGGER `hesk_replies_update_au`
    AFTER UPDATE ON `hesk_replies` FOR EACH ROW
    BEGIN
      INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
      VALUES (NEW.`replyto`,0,NOW())
      ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
      IF COALESCE(NEW.`staffid`,0) > 0 THEN
        INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
        VALUES (NEW.`replyto`,NEW.`staffid`,NOW())
        ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
      END IF;
    END;

    DROP TRIGGER IF EXISTS `hesk_replies_delete_ad`;
    CREATE TRIGGER `hesk_replies_delete_ad`
    AFTER DELETE ON `hesk_replies` FOR EACH ROW
    BEGIN
      INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
      VALUES (OLD.`replyto`,0,NOW())
      ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
      IF COALESCE(OLD.`staffid`,0) > 0 THEN
        INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
        VALUES (OLD.`replyto`,OLD.`staffid`,NOW())
        ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
      END IF;
    END;

    DROP TRIGGER IF EXISTS `hesk_notes_insert_ai`;
    CREATE TRIGGER `hesk_notes_insert_ai`
    AFTER INSERT ON `hesk_notes` FOR EACH ROW
    BEGIN
      INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
      VALUES (NEW.`ticket`,0,NOW())
      ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
      IF COALESCE(NEW.`who`,0) > 0 THEN
        INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
        VALUES (NEW.`ticket`,NEW.`who`,NOW())
        ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
      END IF;
    END;

    DROP TRIGGER IF EXISTS `hesk_notes_update_au`;
    CREATE TRIGGER `hesk_notes_update_au`
    AFTER UPDATE ON `hesk_notes` FOR EACH ROW
    BEGIN
      INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
      VALUES (NEW.`ticket`,0,NOW())
      ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
      IF COALESCE(NEW.`who`,0) > 0 THEN
        INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
        VALUES (NEW.`ticket`,NEW.`who`,NOW())
        ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
      END IF;
    END;

    DROP TRIGGER IF EXISTS `hesk_notes_delete_ad`;
    CREATE TRIGGER `hesk_notes_delete_ad`
    AFTER DELETE ON `hesk_notes` FOR EACH ROW
    BEGIN
      INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
      VALUES (OLD.`ticket`,0,NOW())
      ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
      IF COALESCE(OLD.`who`,0) > 0 THEN
        INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
        VALUES (OLD.`ticket`,OLD.`who`,NOW())
        ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
      END IF;
    END;

    DROP TRIGGER IF EXISTS `hesk_attachments_insert_ai`;
    CREATE TRIGGER `hesk_attachments_insert_ai`
    AFTER INSERT ON `hesk_attachments` FOR EACH ROW
    BEGIN
      DECLARE tid MEDIUMINT UNSIGNED;
      SELECT `id` INTO tid FROM `hesk_tickets` WHERE `trackid` = NEW.`ticket_id` LIMIT 1;
      IF tid IS NOT NULL THEN
        INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
        VALUES (tid,0,NOW())
        ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
        IF COALESCE(@HESK_LASTCHANGE_BY,0) > 0 THEN
          INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
          VALUES (tid,@HESK_LASTCHANGE_BY,NOW())
          ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
        END IF;
      END IF;
    END;

    DROP TRIGGER IF EXISTS `hesk_attachments_delete_ad`;
    CREATE TRIGGER `hesk_attachments_delete_ad`
    AFTER DELETE ON `hesk_attachments` FOR EACH ROW
    BEGIN
      DECLARE tid MEDIUMINT UNSIGNED;
      SELECT `id` INTO tid FROM `hesk_tickets` WHERE `trackid` = OLD.`ticket_id` LIMIT 1;
      IF tid IS NOT NULL THEN
        INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
        VALUES (tid,0,NOW())
        ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
        IF COALESCE(@HESK_LASTCHANGE_BY,0) > 0 THEN
          INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
          VALUES (tid,@HESK_LASTCHANGE_BY,NOW())
          ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
        END IF;
      END IF;
    END;
    SQL;
}


