-- ========================================
-- 📝 Migration: Rename user_email → nomor_wa
-- ========================================
-- Mengubah nama kolom user_email menjadi nomor_wa agar lebih jelas
-- Karena sekarang kolom ini menyimpan nomor WhatsApp, bukan email user

-- Backup data dulu (optional, tapi recommended)
-- CREATE TABLE change_logs_backup AS SELECT * FROM change_logs;

-- Rename column
ALTER TABLE `change_logs`
CHANGE COLUMN `user_email` `nomor_wa` VARCHAR(255) DEFAULT NULL COMMENT 'Nomor WA Client (format 62xxx)';

-- Update index name juga (optional)
-- DROP INDEX `idx_user_email` ON `change_logs`;
-- CREATE INDEX `idx_nomor_wa` ON `change_logs`(`nomor_wa`);

-- Selesai! Kolom user_email sekarang jadi nomor_wa
