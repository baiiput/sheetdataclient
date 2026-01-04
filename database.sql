-- ========================================
-- 📊 SHEET TRACKING DATABASE SCHEMA
-- ========================================
-- Database untuk menyimpan semua perubahan Google Sheet
-- Support: MySQL 5.7+ / MariaDB 10.2+

-- ========================================
-- 🗄️ DATABASE CREATION
-- ========================================

CREATE DATABASE IF NOT EXISTS sheet_tracking
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE sheet_tracking;

-- ========================================
-- 📋 TABLE: change_logs
-- ========================================
-- Tabel utama untuk menyimpan semua log perubahan

CREATE TABLE IF NOT EXISTS change_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

  -- Informasi Spreadsheet
  spreadsheet_id VARCHAR(255) NOT NULL COMMENT 'Google Spreadsheet ID',
  spreadsheet_name VARCHAR(255) NOT NULL COMMENT 'Nama Spreadsheet',
  sheet_name VARCHAR(255) NOT NULL COMMENT 'Nama Sheet yang diubah',

  -- Informasi User & Waktu
  user_email VARCHAR(255) NOT NULL COMMENT 'Email user yang melakukan perubahan',
  changed_at DATETIME NOT NULL COMMENT 'Waktu perubahan (timezone Asia/Jakarta)',

  -- Informasi Cell/Lokasi
  row_number INT UNSIGNED NOT NULL COMMENT 'Nomor baris yang diubah',
  column_number INT UNSIGNED NOT NULL COMMENT 'Nomor kolom (1=A, 2=B, dst)',
  column_name VARCHAR(10) NOT NULL COMMENT 'Nama kolom (A, B, C, dst)',
  cell_address VARCHAR(20) NOT NULL COMMENT 'Alamat cell (contoh: A5, B10)',

  -- Informasi Perubahan
  old_value TEXT COMMENT 'Nilai sebelum diubah',
  new_value TEXT COMMENT 'Nilai setelah diubah',

  -- Informasi Tambahan (untuk memudahkan pencarian)
  client_name VARCHAR(255) DEFAULT NULL COMMENT 'Nama client (dari kolom A)',
  kit_number VARCHAR(100) DEFAULT NULL COMMENT 'KIT Number (dari kolom I)',

  -- Metadata
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu data masuk ke database',
  ip_address VARCHAR(45) DEFAULT NULL COMMENT 'IP address dari API request',

  -- Index untuk performa pencarian
  INDEX idx_spreadsheet (spreadsheet_id),
  INDEX idx_sheet_name (sheet_name),
  INDEX idx_user_email (user_email),
  INDEX idx_changed_at (changed_at),
  INDEX idx_client_name (client_name),
  INDEX idx_kit_number (kit_number),
  INDEX idx_cell (sheet_name, row_number, column_name),
  INDEX idx_search (sheet_name, user_email, changed_at)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Log semua perubahan Google Sheet';

-- ========================================
-- 📊 TABLE: users (opsional - untuk mengelola user)
-- ========================================

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  name VARCHAR(255) DEFAULT NULL,
  role ENUM('admin', 'editor', 'viewer') DEFAULT 'editor',
  is_active TINYINT(1) DEFAULT 1,
  last_activity DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_email (email),
  INDEX idx_role (role)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Daftar user yang mengakses sheet';

-- ========================================
-- 📈 TABLE: statistics (opsional - untuk dashboard)
-- ========================================

CREATE TABLE IF NOT EXISTS daily_statistics (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  date DATE NOT NULL,
  sheet_name VARCHAR(255) NOT NULL,
  user_email VARCHAR(255) NOT NULL,
  total_changes INT UNSIGNED DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY unique_daily_stat (date, sheet_name, user_email),
  INDEX idx_date (date),
  INDEX idx_sheet (sheet_name)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Statistik harian perubahan sheet';

-- ========================================
-- 🔐 TABLE: admin_users (untuk login dashboard)
-- ========================================

CREATE TABLE IF NOT EXISTS admin_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL COMMENT 'Password hash (bcrypt)',
  email VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  is_active TINYINT(1) DEFAULT 1,
  last_login DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_username (username),
  INDEX idx_email (email)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Admin users untuk akses dashboard';

-- ========================================
-- 📝 INSERT DEFAULT ADMIN
-- ========================================
-- Username: admin
-- Password: admin123 (GANTI SETELAH INSTALL!)

INSERT INTO admin_users (username, password, email, name) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'Administrator');

-- Password hash untuk 'admin123' - WAJIB GANTI setelah install!

-- ========================================
-- 🔍 VIEWS untuk Query Mudah
-- ========================================

-- View untuk melihat perubahan terbaru dengan info lengkap
CREATE OR REPLACE VIEW v_recent_changes AS
SELECT
  cl.id,
  cl.spreadsheet_name,
  cl.sheet_name,
  cl.user_email,
  cl.changed_at,
  CONCAT(cl.column_name, cl.row_number) AS cell,
  cl.old_value,
  cl.new_value,
  cl.client_name,
  cl.kit_number,
  CASE
    WHEN cl.old_value = '' OR cl.old_value IS NULL THEN 'INSERT'
    WHEN cl.new_value = '' OR cl.new_value IS NULL THEN 'DELETE'
    ELSE 'UPDATE'
  END AS change_type,
  TIMESTAMPDIFF(HOUR, cl.changed_at, NOW()) AS hours_ago
FROM change_logs cl
ORDER BY cl.changed_at DESC;

-- View untuk statistik per user
CREATE OR REPLACE VIEW v_user_statistics AS
SELECT
  user_email,
  COUNT(*) AS total_changes,
  COUNT(DISTINCT sheet_name) AS sheets_modified,
  COUNT(DISTINCT DATE(changed_at)) AS active_days,
  MAX(changed_at) AS last_activity,
  MIN(changed_at) AS first_activity
FROM change_logs
GROUP BY user_email
ORDER BY total_changes DESC;

-- View untuk statistik per sheet
CREATE OR REPLACE VIEW v_sheet_statistics AS
SELECT
  sheet_name,
  COUNT(*) AS total_changes,
  COUNT(DISTINCT user_email) AS unique_users,
  COUNT(DISTINCT DATE(changed_at)) AS active_days,
  MAX(changed_at) AS last_modified,
  MIN(changed_at) AS first_modified
FROM change_logs
GROUP BY sheet_name
ORDER BY total_changes DESC;

-- ========================================
-- 🧹 STORED PROCEDURE: Cleanup Old Logs
-- ========================================
-- Prosedur untuk menghapus log lama (opsional, jalankan via cron)

DELIMITER //

CREATE PROCEDURE cleanup_old_logs(IN days_to_keep INT)
BEGIN
  DELETE FROM change_logs
  WHERE changed_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);

  SELECT ROW_COUNT() AS deleted_rows;
END //

DELIMITER ;

-- Contoh penggunaan: CALL cleanup_old_logs(365); -- Hapus log lebih dari 1 tahun

-- ========================================
-- 📊 SAMPLE QUERIES
-- ========================================

-- Query untuk mencari perubahan berdasarkan client name
-- SELECT * FROM change_logs WHERE client_name LIKE '%nama client%' ORDER BY changed_at DESC;

-- Query untuk melihat perubahan hari ini
-- SELECT * FROM v_recent_changes WHERE DATE(changed_at) = CURDATE();

-- Query untuk melihat aktivitas user tertentu
-- SELECT * FROM change_logs WHERE user_email = 'user@example.com' ORDER BY changed_at DESC LIMIT 100;

-- Query untuk melihat perubahan di sheet tertentu
-- SELECT * FROM change_logs WHERE sheet_name = 'Client Aktif' ORDER BY changed_at DESC LIMIT 100;

-- Query untuk statistik harian
-- SELECT DATE(changed_at) as date, COUNT(*) as total_changes
-- FROM change_logs
-- GROUP BY DATE(changed_at)
-- ORDER BY date DESC
-- LIMIT 30;

-- ========================================
-- ✅ VERIFICATION QUERIES
-- ========================================

-- Cek struktur tabel
-- SHOW TABLES;
-- DESCRIBE change_logs;

-- Cek indexes
-- SHOW INDEX FROM change_logs;

-- Cek size database
-- SELECT
--   table_name AS 'Table',
--   ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
-- FROM information_schema.TABLES
-- WHERE table_schema = 'sheet_tracking'
-- ORDER BY (data_length + index_length) DESC;

-- ========================================
-- 🎉 DATABASE SETUP COMPLETE!
-- ========================================
-- Selanjutnya:
-- 1. Import file ini: mysql -u root -p < database.sql
-- 2. Buat user database (opsional tapi recommended):
--    CREATE USER 'sheet_tracker'@'localhost' IDENTIFIED BY 'password_kuat_anda';
--    GRANT ALL PRIVILEGES ON sheet_tracking.* TO 'sheet_tracker'@'localhost';
--    FLUSH PRIVILEGES;
-- 3. Update config.php dengan credentials database
-- 4. Test koneksi dari PHP
