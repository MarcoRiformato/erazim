DELIMITER //

DROP TRIGGER IF EXISTS `hesk_tickets_updates_au`//
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
END//

DROP TRIGGER IF EXISTS `hesk_tickets_insert_ai`//
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
END//

DROP TRIGGER IF EXISTS `hesk_replies_updates_ai`//
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
END//

DROP TRIGGER IF EXISTS `hesk_replies_update_au`//
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
END//

DROP TRIGGER IF EXISTS `hesk_replies_delete_ad`//
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
END//

DROP TRIGGER IF EXISTS `hesk_notes_insert_ai`//
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
END//

DROP TRIGGER IF EXISTS `hesk_notes_update_au`//
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
END//

DROP TRIGGER IF EXISTS `hesk_notes_delete_ad`//
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
END//

DROP TRIGGER IF EXISTS `hesk_attachments_insert_ai`//
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
END//

DROP TRIGGER IF EXISTS `hesk_attachments_delete_ad`//
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
END//

DROP TRIGGER IF EXISTS `hesk_reply_drafts_insert_ai`//
CREATE TRIGGER `hesk_reply_drafts_insert_ai`
AFTER INSERT ON `hesk_reply_drafts` FOR EACH ROW
BEGIN
  INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
  VALUES (NEW.`ticket`,0,NOW())
  ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
  IF COALESCE(NEW.`owner`,0) > 0 THEN
    INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
    VALUES (NEW.`ticket`,NEW.`owner`,NOW())
    ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
  END IF;
END//

DROP TRIGGER IF EXISTS `hesk_reply_drafts_update_au`//
CREATE TRIGGER `hesk_reply_drafts_update_au`
AFTER UPDATE ON `hesk_reply_drafts` FOR EACH ROW
BEGIN
  INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
  VALUES (NEW.`ticket`,0,NOW())
  ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
  IF COALESCE(NEW.`owner`,0) > 0 THEN
    INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
    VALUES (NEW.`ticket`,NEW.`owner`,NOW())
    ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
  END IF;
END//

DROP TRIGGER IF EXISTS `hesk_reply_drafts_delete_ad`//
CREATE TRIGGER `hesk_reply_drafts_delete_ad`
AFTER DELETE ON `hesk_reply_drafts` FOR EACH ROW
BEGIN
  INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
  VALUES (OLD.`ticket`,0,NOW())
  ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
  IF COALESCE(OLD.`owner`,0) > 0 THEN
    INSERT INTO `hesk_ticket_updates` (`ticket_id`,`staff_id`,`changed_at`)
    VALUES (OLD.`ticket`,OLD.`owner`,NOW())
    ON DUPLICATE KEY UPDATE `changed_at`=VALUES(`changed_at`);
  END IF;
END//

DELIMITER ;


