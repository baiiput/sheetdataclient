# ✅ COMBINED: appsscript.json untuk Semua Script

File ini **menggabungkan semua permission** yang dibutuhkan untuk:
1. **tracking-script.gs** → UrlFetchApp.fetch() (external_request)
2. **kirimWhatsApp.gs** → MailApp.sendEmail() (send_mail)

---

## 📄 File appsscript.json (FINAL - All in One):

```json
{
  "timeZone": "Asia/Jakarta",
  "dependencies": {},
  "exceptionLogging": "STACKDRIVER",
  "oauthScopes": [
    "https://www.googleapis.com/auth/spreadsheets",
    "https://www.googleapis.com/auth/script.external_request",
    "https://www.googleapis.com/auth/script.send_mail"
  ],
  "runtimeVersion": "V8"
}
```

---

## 🔧 Cara Install:

### **STEP 1: Update/Buat File appsscript.json**

1. **Buka Google Sheets** → **Extensions** → **Apps Script**
2. Di sidebar kiri:
   - Kalau sudah ada file `appsscript.json` → Klik file tersebut
   - Kalau belum ada → Klik **+** (Add a file) → Pilih **JSON**
3. **Hapus semua isi** file
4. **Copy paste** JSON di atas
5. **Save** (Ctrl+S)

---

### **STEP 2: Reset Authorization**

1. Buka: **https://myaccount.google.com/permissions**
2. Cari project Apps Script kamu
3. Klik **Remove Access**
4. Konfirmasi

---

### **STEP 3: Re-Authorize (Pilih Salah Satu)**

#### **Opsi A: Via tracking-script**
1. Apps Script Editor
2. Function dropdown → Pilih **`onEditTracking`**
3. Klik **Run** (▶️)
4. Authorization popup → **Review Permissions**
5. **Advanced** → **Go to [Project] (unsafe)**
6. Allow permissions:
   - ✅ View and manage spreadsheets
   - ✅ Connect to an external service
   - ✅ Send email as you
7. Klik **Allow**

#### **Opsi B: Via kirimWhatsApp**
1. Apps Script Editor
2. Function dropdown → Pilih **`kirimWhatsApp`**
3. Klik **Run** (▶️)
4. (Sama seperti opsi A)

**Keduanya akan kasih permission yang SAMA**, jadi pilih salah satu aja.

---

## ✅ Test Semua Script:

### **Test 1: Tracking Script**
1. Edit cell di spreadsheet
2. Tunggu 2-3 detik
3. Refresh dashboard → data baru muncul
4. ✅ **Berhasil!**

### **Test 2: kirimWhatsApp Script**
1. Run function `kirimWhatsApp()`
2. Cek inbox email → ada summary
3. Cek spreadsheet → ada link WA
4. ✅ **Berhasil!**

---

## 📋 Permission Yang Dibutuhkan:

| Permission | Untuk Apa | Script |
|------------|-----------|--------|
| `spreadsheets` | Akses/edit Google Sheets | Semua script |
| `external_request` | Kirim data ke API eksternal | tracking-script.gs |
| `send_mail` | Kirim email notification | kirimWhatsApp.gs |

**Semua permission ini AMAN** dan sudah standard untuk Apps Script.

---

## ⚠️ PENTING:

- **Jangan hapus** permission manapun dari `appsscript.json`
- **Jangan tambah** permission yang tidak perlu
- **Harus re-authorize** setiap kali update `appsscript.json`
- **Cukup 1 file** `appsscript.json` untuk SEMUA script dalam project

---

## 🔍 Troubleshooting:

### **Error: "permissions not sufficient" masih muncul**
→ Re-authorize lagi (STEP 2 & 3)

### **Authorization popup tidak muncul**
→ Logout Google, clear cache, login lagi

### **Email tidak terkirim tapi tracking jalan**
→ Cek quota email: `MailApp.getRemainingDailyQuota()`

### **Tracking tidak jalan tapi email jalan**
→ Cek TRACKING_API_URL benar atau tidak

---

## ✅ Selesai!

Dengan file ini:
- ✅ tracking-script.gs → Jalan normal
- ✅ kirimWhatsApp.gs → Jalan normal
- ✅ Semua permission → Sudah lengkap
- ✅ Satu file → Untuk semua script

**Tidak perlu file terpisah!** Satu `appsscript.json` cukup untuk semua.
