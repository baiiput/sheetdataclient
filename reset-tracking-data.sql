-- ========================================
-- 🗑️ Reset Tracking Data - Hapus Semua Log
-- ========================================
-- PERINGATAN: Ini akan MENGHAPUS SEMUA data tracking!
-- Gunakan dengan hati-hati!

-- Opsi 1: TRUNCATE (Lebih cepat, reset AUTO_INCREMENT)
-- Gunakan ini jika ingin ID mulai dari 1 lagi
TRUNCATE TABLE `change_logs`;

-- Opsi 2: DELETE (Lebih lambat, AUTO_INCREMENT tetap lanjut)
-- DELETE FROM `change_logs`;

-- Opsi 3: Delete berdasarkan tanggal tertentu
-- Misalnya hapus data sebelum 2026-01-01
-- DELETE FROM `change_logs` WHERE changed_at < '2026-01-01';

-- Opsi 4: Delete berdasarkan sheet tertentu
-- DELETE FROM `change_logs` WHERE sheet_name = 'Client Aktif';

-- Verifikasi data sudah kosong
-- SELECT COUNT(*) as total_rows FROM `change_logs`;

-- ========================================
-- CATATAN:
-- - TRUNCATE = Hapus semua + reset ID ke 1
-- - DELETE = Hapus semua tapi ID lanjut dari nomor terakhir
-- - Backup dulu jika data penting: CREATE TABLE backup AS SELECT * FROM change_logs;
-- ========================================
