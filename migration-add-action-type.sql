-- ========================================
-- 📊 MIGRATION: Add action_type column
-- ========================================
-- Run this to add action_type tracking to existing database

USE sheet_tracking;

-- Add action_type column
ALTER TABLE `change_logs`
ADD COLUMN `action_type` ENUM('UPDATE', 'INSERT', 'DELETE', 'INSERT_ROW', 'DELETE_ROW')
DEFAULT 'UPDATE'
COMMENT 'Type of action: UPDATE (cell edit), INSERT (new value), DELETE (value removed), INSERT_ROW (new row), DELETE_ROW (row deleted)'
AFTER `new_value`;

-- Add index for action_type
ALTER TABLE `change_logs`
ADD INDEX `idx_action_type` (`action_type`);

-- Update existing records based on old_value/new_value
UPDATE `change_logs`
SET `action_type` = CASE
  WHEN (`old_value` = '' OR `old_value` IS NULL) AND (`new_value` != '' AND `new_value` IS NOT NULL) THEN 'INSERT'
  WHEN (`new_value` = '' OR `new_value` IS NULL) AND (`old_value` != '' AND `old_value` IS NOT NULL) THEN 'DELETE'
  ELSE 'UPDATE'
END;

-- Verify migration
SELECT
  `action_type`,
  COUNT(*) as count
FROM `change_logs`
GROUP BY `action_type`;

-- ========================================
-- ✅ MIGRATION COMPLETE!
-- ========================================
-- Jalankan dengan: mysql -u root -p sheet_tracking < migration-add-action-type.sql
