-- =====================================================
-- HESK 3.5.0 Auth Tokens Migration
-- =====================================================
-- 
-- Adds the user_type column to hesk_auth_tokens table
-- This column is required for HESK 3.5.0 compatibility
-- 
-- Run this migration after upgrading from HESK 3.4.x to 3.5.0
-- 
-- Usage:
--   mysql -u root -p erazim < db/hesk_35_auth_tokens.sql
-- 
-- =====================================================

USE erazim;

-- Add user_type column to hesk_auth_tokens if it doesn't exist
-- This column distinguishes between STAFF and CUSTOMER authentication tokens
SET @dbname = DATABASE();
SET @tablename = 'hesk_auth_tokens';
SET @columnname = 'user_type';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  'SELECT 1;',  -- Column exists, do nothing
  CONCAT(
    'ALTER TABLE `', @tablename, '` ',
    'ADD COLUMN `', @columnname, '` ENUM(''STAFF'', ''CUSTOMER'') NOT NULL DEFAULT ''STAFF'' ',
    'AFTER `user_id`;'
  )
));

PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Show result
SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN 'SUCCESS: user_type column exists in hesk_auth_tokens'
        ELSE 'ERROR: user_type column was not created'
    END AS migration_status
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'hesk_auth_tokens'
    AND COLUMN_NAME = 'user_type';

