# 📱 Instalasi Sheet Tracking dengan WhatsApp Number

## 🎯 Fitur Baru

- ✅ Track **SEMUA sheet** di spreadsheet (bukan hanya Client Aktif/Lepas/Non Aktif)
- ✅ Replace user email dengan **Nomor WhatsApp Client**
- ✅ Nomor WA otomatis jadi **clickable link** ke wa.me
- ✅ Auto-format nomor WA ke format internasional (62xxx)
- ✅ Hapus filter user yang tidak berguna
- ✅ Simple & clean tracking

---

## 📋 STEP 1: Update Google Apps Script

### 1.1 Buka Apps Script Editor

1. Buka Google Sheet Anda
2. Klik **Extensions → Apps Script**

### 1.2 Replace Script Lama

1. Di file **Code.gs**, **DELETE semua kode lama**
2. **Copy paste** script baru dari file **`tracking-script.gs`**
3. **PENTING: Sesuaikan konfigurasi kolom!**

Di baris 17-20, sesuaikan dengan struktur sheet Anda:

```javascript
// Konfigurasi kolom data
const COLUMN_CLIENT_NAME = 0;  // Kolom A (index 0) = Nama Client
const COLUMN_WA_NUMBER = 1;    // Kolom B (index 1) = Nomor WA - SESUAIKAN INI!
const COLUMN_KIT_NUMBER = 8;   // Kolom I (index 8) = KIT Number
```

**Contoh penyesuaian:**
- Jika Nomor WA di **Kolom C**, ubah jadi: `const COLUMN_WA_NUMBER = 2;` (index 2)
- Jika Nomor WA di **Kolom D**, ubah jadi: `const COLUMN_WA_NUMBER = 3;` (index 3)
- dst.

**Note:** Index dimulai dari 0 (A=0, B=1, C=2, D=3, ...)

4. **Klik Save** (💾)

### 1.3 Pastikan Trigger Sudah Terpasang

1. Klik **⏰ Triggers** (icon jam di sidebar kiri)
2. **Harus ada 2 trigger:**

**TRIGGER 1:**
- Function: `onEditTracking`
- Event: `On edit`

**TRIGGER 2:**
- Function: `onChangeTracking`
- Event: `On change`

3. **Jika trigger belum ada**, install dengan:
   - Klik "+ Add Trigger"
   - Setup sesuai konfigurasi di atas
   - Save & authorize

---

## 📋 STEP 2: Test Tracking

### 2.1 Edit Sebuah Cell

1. Edit cell manapun di sheet (misal: ubah tanggal, nama, dll)
2. Tunggu 5 detik

### 2.2 Cek Execution Log

1. Di Apps Script, klik **Executions** (icon list di sidebar)
2. Lihat log terbaru
3. Harus menunjukkan:

```
📊 Tracking change:
  Sheet: [Nama Sheet]
  WA Number: 628123456789
  Client: [Nama Client]
  Cell: K5
  Old: 10/01/2026
  New: 10/02/2026
  Type: UPDATE
✅ Tracking data sent successfully
```

### 2.3 Cek Dashboard

1. Buka **https://star.octolink.id**
2. Refresh halaman
3. Perubahan ter-record dengan:
   - **Nomor WA** muncul sebagai **clickable link** 💬
   - Klik nomor WA → langsung buka WhatsApp chat!

---

## 🔍 Fitur Tracking

### Yang Akan Di-track:

✅ **SEMUA sheet** di spreadsheet Anda
✅ Cell edit (UPDATE, INSERT, DELETE)
✅ Row insert/delete
✅ Nomor WA Client dari row yang diubah

### Yang TIDAK Di-track:

❌ Sheet yang namanya diawali `_` (contoh: `_Tracking_Log`)
❌ Sheet bernama `Template`
❌ Sheet bernama `Config`

---

## 📱 Format Nomor WhatsApp

Script otomatis format nomor WA:

| Input | Output (di database) |
|---|---|
| 081234567890 | 6281234567890 |
| +62 812-3456-7890 | 6281234567890 |
| 62 812 3456 7890 | 6281234567890 |

**Nomor otomatis:**
- Hapus spasi dan tanda baca
- Ganti 0 awal jadi 62
- Format jadi link wa.me

---

## 🎨 Dashboard Baru

### Perubahan UI:

1. **Filter User dihapus** - tidak berguna karena personal Gmail limitation
2. **Kolom "User" diganti jadi "Nomor WA"**
3. **Nomor WA jadi clickable link:**
   - Klik nomor → buka chat WhatsApp
   - Hover → icon animasi pulse
   - Color: hijau WhatsApp (#25D366)

### Contoh Display:

```
| Waktu | Client | KIT | Nomor WA | Sheet | Perubahan |
|-------|--------|-----|----------|-------|-----------|
| 11/01 | PT ABC | KIT-123 | 💬 628123456789 | Client Aktif | ... |
```

Klik **💬 628123456789** → langsung buka `https://wa.me/628123456789`

---

## ⚙️ Konfigurasi Lanjutan

### Jika Hanya Ingin Track Sheet Tertentu:

Edit baris 15 di `tracking-script.gs`:

```javascript
// BEFORE (track semua):
const TRACKED_SHEETS = [];

// AFTER (track hanya sheet tertentu):
const TRACKED_SHEETS = ['Client Aktif', 'Client Lepas', 'Client Non Aktif'];
```

### Jika Nomor WA di Kolom Lain:

Edit baris 19:

```javascript
const COLUMN_WA_NUMBER = 2;  // Ganti angka sesuai kolom (0=A, 1=B, 2=C, dst)
```

### Jika Client Name di Kolom Lain:

Edit baris 18:

```javascript
const COLUMN_CLIENT_NAME = 1;  // Ganti angka sesuai kolom
```

### Jika KIT Number di Kolom Lain:

Edit baris 20:

```javascript
const COLUMN_KIT_NUMBER = 10;  // Ganti angka sesuai kolom
```

---

## 🐛 Troubleshooting

### Nomor WA Tidak Muncul / Kosong

**Penyebab:** Kolom WA tidak sesuai

**Solusi:**
1. Cek struktur sheet Anda - di kolom mana Nomor WA?
2. Update `COLUMN_WA_NUMBER` di script
3. Save & test lagi

### Nomor WA Muncul Tapi Bukan Nomor WA

**Penyebab:** Salah kolom

**Solusi:**
1. Periksa kembali index kolom (ingat: A=0, B=1, C=2...)
2. Cek apakah ada header row - jika ada, data mulai dari row 2
3. Update konfigurasi

### Sheet Tertentu Tidak Ter-track

**Penyebab:** Sheet dimulai dengan `_` atau bernama `Template`/`Config`

**Solusi:**
1. Rename sheet (hapus `_` di awal)
2. Atau edit script di baris 44-46 untuk menghapus filter tersebut

### Link WA Tidak Bisa Diklik

**Penyebab:** Browser block popup

**Solusi:**
1. Klik kanan link → Open in new tab
2. Atau allow popup dari star.octolink.id

---

## 📊 Auto-Refresh Dashboard

Dashboard sudah dilengkapi **auto-refresh**:

- ✅ Default: ON
- ✅ Interval: 30 detik
- ✅ Progress bar countdown
- ✅ Hanya refresh data (tidak reload halaman)
- ✅ Update otomatis tanpa manual refresh

**Cara pakai:**
1. Buka dashboard
2. Auto-refresh sudah ON by default
3. Lihat progress bar di atas table
4. Tunggu 30 detik → data ter-update otomatis!

**Customize:**
- Klik toggle "🔄 Auto Refresh" untuk ON/OFF
- Pilih interval: 30s / 1min / 2min / 5min

---

## ✅ Checklist Instalasi

- [ ] Script `tracking-script.gs` sudah di-copy ke Code.gs
- [ ] Konfigurasi `COLUMN_WA_NUMBER` sudah disesuaikan
- [ ] Trigger `onEditTracking` (On edit) sudah terpasang
- [ ] Trigger `onChangeTracking` (On change) sudah terpasang
- [ ] Test edit cell → cek execution log → success
- [ ] Dashboard sudah menampilkan nomor WA sebagai link
- [ ] Klik link WA → buka WhatsApp chat → work!

---

## 📞 Support

Jika ada masalah, cek:

1. **Execution log** di Apps Script untuk error messages
2. **_Tracking_Log sheet** untuk fallback log (jika API gagal)
3. **Network tab** di browser console untuk error API

File yang sudah **DIHAPUS** (tidak perlu lagi):
- ❌ SOLUSI-PERSONAL-GMAIL.md
- ❌ TROUBLESHOOTING-TRACKING.md
- ❌ tracking-script-USER-INPUT.gs
- ❌ tracking-script-READY.gs
- ❌ tracking-script-FINAL-WITH-USER-MENU.gs

**Hanya gunakan:** `tracking-script.gs` (file final yang sudah clean)

---

## 🎉 Done!

Tracking system Anda sudah siap:
- 📱 Track dengan Nomor WA (bukan user email)
- 🌍 Track SEMUA sheet otomatis
- 💬 WhatsApp link clickable
- 🔄 Auto-refresh dashboard
- ✨ Clean & simple!

Enjoy your tracking system! 🚀
