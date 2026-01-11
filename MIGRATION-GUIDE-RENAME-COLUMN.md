# 🔄 Migration Guide: Rename user_email → nomor_wa

## 📋 Overview

Kolom `user_email` di database sekarang menyimpan **nomor WhatsApp** (bukan email), jadi lebih baik diganti nama jadi `nomor_wa` agar tidak membingungkan.

---

## ⚠️ PERINGATAN - Breaking Change!

Setelah migration ini:
- ❌ Tracking script lama **TIDAK AKAN JALAN** (perlu update)
- ❌ Dashboard lama **TIDAK AKAN TAMPIL** data (perlu update)
- ✅ Tapi data di database **TETAP ADA** (tidak hilang)

**Rekomendasi:** Lakukan di jam sepi atau maintenance window.

---

## 🔧 Langkah-Langkah Migration:

### **STEP 1: Backup Database** ⚠️ WAJIB!

```sql
-- Backup table
CREATE TABLE change_logs_backup AS SELECT * FROM change_logs;

-- Atau export via phpMyAdmin / mysqldump
mysqldump -u sheet_tracker -p sheet_tracking change_logs > change_logs_backup.sql
```

### **STEP 2: Run Migration SQL**

```bash
# Via MySQL command line
mysql -u sheet_tracker -p sheet_tracking < migration-rename-user-email-to-nomor-wa.sql

# Atau via phpMyAdmin:
# 1. Buka tab SQL
# 2. Paste isi migration-rename-user-email-to-nomor-wa.sql
# 3. Klik Go/Kirim
```

**Isi SQL:**
```sql
ALTER TABLE `change_logs`
CHANGE COLUMN `user_email` `nomor_wa` VARCHAR(255) DEFAULT NULL COMMENT 'Nomor WA Client (format 62xxx)';
```

### **STEP 3: Verifikasi Database**

```sql
-- Cek kolom sudah berubah
SHOW COLUMNS FROM change_logs LIKE 'nomor_wa';

-- Cek data masih ada
SELECT COUNT(*) FROM change_logs;
SELECT * FROM change_logs LIMIT 5;
```

### **STEP 4: Update Codebase**

Ada **4 file utama** yang perlu diupdate:

#### **A. api/track-change.php**

**Cari:**
```php
'user_email' => sanitize($data['user_email']),
```

**Ganti jadi:**
```php
'nomor_wa' => sanitize($data['user_email']), // Note: input tetap user_email dari Apps Script
```

**ATAU** update Apps Script untuk kirim field `nomor_wa` instead.

#### **B. api/get-changes.php**

**Cari:**
```php
'user_email' => $log['user_email'],
```

**Ganti jadi:**
```php
'nomor_wa' => $log['nomor_wa'],
```

**Dan cari:**
```php
$groupKey = ($log['kit_number'] ?? '') . '|' . ($log['client_name'] ?? '') . '|' . $date . '|' . ($log['user_email'] ?? '');
```

**Ganti jadi:**
```php
$groupKey = ($log['kit_number'] ?? '') . '|' . ($log['client_name'] ?? '') . '|' . $date . '|' . ($log['nomor_wa'] ?? '');
```

#### **C. includes/functions.php**

Cari semua reference `user_email` dan ganti jadi `nomor_wa`.

**Contoh:**
```php
// BEFORE
SELECT * FROM change_logs WHERE user_email = :wa

// AFTER
SELECT * FROM change_logs WHERE nomor_wa = :wa
```

#### **D. index.php**

**Cari:**
```php
$group['user_email']
$log['user_email']
formatWALink($group['user_email'])
formatWALink($log['user_email'])
```

**Ganti jadi:**
```php
$group['nomor_wa']
$log['nomor_wa']
formatWALink($group['nomor_wa'])
formatWALink($log['nomor_wa'])
```

**JavaScript:**
```javascript
// BEFORE
group.user_email
log.user_email

// AFTER
group.nomor_wa
log.nomor_wa
```

### **STEP 5: Update Tracking Script (Apps Script)**

**File:** `tracking-script.gs`

**Cari:**
```javascript
var trackingData = {
  // ...
  user_email: waNumber,
  // ...
};
```

**OPTIONAL - Ganti jadi:**
```javascript
var trackingData = {
  // ...
  nomor_wa: waNumber,  // Atau tetap user_email, API akan handle
  // ...
};
```

**CATATAN:** Kalau mau backward compatible, bisa kirim **KEDUANYA**:
```javascript
var trackingData = {
  // ...
  user_email: waNumber,  // Backward compatibility
  nomor_wa: waNumber,    // New field
  // ...
};
```

### **STEP 6: Test Semua**

1. ✅ Run tracking script → Edit cell di spreadsheet
2. ✅ Cek database → Data masuk dengan nomor_wa terisi
3. ✅ Buka dashboard → Data tampil normal
4. ✅ Auto-refresh → Jalan normal
5. ✅ Copy button WA → Berfungsi
6. ✅ Filter & search → Berfungsi

---

## 🔄 Alternative: Alias Column (Backward Compatible)

Kalau tidak mau breaking change, bisa pakai **VIEW** atau **ALIAS**:

```sql
-- Buat view yang alias user_email → nomor_wa
CREATE OR REPLACE VIEW change_logs_view AS
SELECT
  id,
  spreadsheet_id,
  spreadsheet_name,
  sheet_name,
  user_email as nomor_wa,  -- Alias
  changed_at,
  row_num,
  column_num,
  column_name,
  cell_address,
  old_value,
  new_value,
  client_name,
  account,
  kit_number,
  action_type,
  ip_address,
  created_at
FROM change_logs;
```

Tapi ini **NOT RECOMMENDED** karena:
- Lebih lambat (view overhead)
- Masih confusing (user_email vs nomor_wa)
- Temporary solution only

---

## 🗑️ Bonus: Reset Tracking Data

Kalau mau hapus semua tracking data dan mulai dari 0:

```sql
-- Opsi 1: TRUNCATE (Reset ID ke 1)
TRUNCATE TABLE change_logs;

-- Opsi 2: DELETE (ID lanjut dari nomor terakhir)
DELETE FROM change_logs;

-- Opsi 3: Delete data lama (sebelum tanggal tertentu)
DELETE FROM change_logs WHERE changed_at < '2026-01-01';
```

**File SQL:** `reset-tracking-data.sql`

---

## 📋 Checklist Migration:

- [ ] Backup database (WAJIB!)
- [ ] Run migration SQL (ALTER TABLE)
- [ ] Verifikasi kolom `nomor_wa` exist
- [ ] Update api/track-change.php
- [ ] Update api/get-changes.php
- [ ] Update includes/functions.php
- [ ] Update index.php (PHP + JavaScript)
- [ ] Update tracking-script.gs (optional)
- [ ] Git commit + push
- [ ] Deploy ke VPS
- [ ] Test tracking berfungsi
- [ ] Test dashboard tampil data
- [ ] Test auto-refresh
- [ ] Test copy button WA

---

## 🆘 Rollback Plan

Kalau ada masalah serius:

```sql
-- Restore dari backup
DROP TABLE change_logs;
CREATE TABLE change_logs AS SELECT * FROM change_logs_backup;

-- Atau rename kembali
ALTER TABLE `change_logs`
CHANGE COLUMN `nomor_wa` `user_email` VARCHAR(255) DEFAULT NULL;
```

Lalu revert semua perubahan di codebase.

---

## ✅ Selesai!

Setelah migration:
- ✅ Kolom database lebih jelas: `nomor_wa`
- ✅ Tidak bingung lagi (bukan email user)
- ✅ Semantically correct
- ✅ Code lebih mudah dibaca

**Estimated Time:** 15-30 menit (tergantung jumlah data)
