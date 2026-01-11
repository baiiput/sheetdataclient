# 🔧 Fix: UrlFetchApp Permission Error - Tracking Script

## ❌ Error:
```
Specified permissions are not sufficient to call UrlFetchApp.fetch.
Required permissions: https://www.googleapis.com/auth/script.external_request
```

## 🎯 Root Cause:
Tracking script menggunakan `UrlFetchApp.fetch()` untuk kirim data ke API, tapi **OAuth scope** belum di-set di Apps Script project.

---

## ✅ SOLUSI (5 Menit):

### **STEP 1: Buat File appsscript.json**

1. **Buka Google Sheets** → **Extensions** → **Apps Script**
2. Di sidebar kiri, klik **+** (Add a file)
3. Pilih **JSON** (bukan Script!)
4. Nama file akan otomatis: `appsscript.json`
5. **Paste kode ini:**

```json
{
  "timeZone": "Asia/Jakarta",
  "dependencies": {},
  "exceptionLogging": "STACKDRIVER",
  "oauthScopes": [
    "https://www.googleapis.com/auth/spreadsheets",
    "https://www.googleapis.com/auth/script.external_request"
  ],
  "runtimeVersion": "V8"
}
```

6. **Save** (Ctrl+S)

---

### **STEP 2: Reset Authorization Lama**

1. Buka: **https://myaccount.google.com/permissions**
2. Cari project Apps Script tracking (biasanya nama: "Starlink Management" atau nama spreadsheet kamu)
3. Klik project tersebut
4. Klik **Remove Access** atau **Hapus Akses**
5. Konfirmasi hapus

---

### **STEP 3: Re-Authorize dengan Permission Baru**

1. **Kembali ke Apps Script Editor**
2. Di toolbar atas, pilih fungsi: **`onEditTracking`** (dropdown)
3. Klik **Run** (▶️ icon play)
4. Akan muncul popup **"Authorization required"**
5. Klik **Review Permissions**
6. Pilih **akun Google** kamu
7. Akan muncul warning **"Google hasn't verified this app"**
8. Klik **Advanced** (di bawah)
9. Klik **Go to [Project Name] (unsafe)**
10. Scroll ke bawah, lihat permission list:
    - ✅ View and manage your spreadsheets in Google Drive
    - ✅ Connect to an external service
11. Klik **Allow** atau **Izinkan**

---

### **STEP 4: Verifikasi Trigger**

1. Di Apps Script sidebar, klik icon **⏰ Triggers**
2. Pastikan ada **2 trigger**:
   - **onEditTracking** → On edit
   - **onChangeTracking** → On change
3. Kalau belum ada, **tambah trigger** seperti biasa

---

### **STEP 5: Test Tracking**

1. **Edit cell** di spreadsheet (ubah nilai apa saja)
2. Tunggu **2-3 detik**
3. **Refresh dashboard** tracking
4. Data baru **harus muncul** di dashboard
5. ✅ **Selesai!** Error hilang

---

## 🔍 Troubleshooting:

### **Error: "Authorization required" tidak muncul**
**Solusi:**
- Logout dari Google Account
- Clear cache browser (Ctrl+Shift+Del)
- Login lagi
- Coba run function lagi

### **Error: "Exception: Service using standard Google..."**
**Solusi:**
- Pastikan `appsscript.json` **sudah di-save**
- Tutup Apps Script Editor
- Buka lagi
- Ulangi STEP 3

### **Data tidak masuk dashboard tapi tidak ada error**
**Solusi:**
1. Cek **Executions** (icon ⚡ di sidebar)
2. Lihat log terakhir, ada error atau tidak
3. Pastikan `TRACKING_API_URL` benar:
   ```javascript
   const TRACKING_API_URL = 'https://star.octolink.id/api/track-change.php';
   ```
4. Test manual: Run function `testTracking()`

### **Error: "Script function not found: onEditTracking"**
**Solusi:**
- Pastikan nama fungsi di script: `onEditTracking` (bukan `onEdit`)
- Pastikan file script sudah di-save

---

## 📋 Checklist Verification:

Setelah selesai semua langkah, pastikan:

- [ ] File `appsscript.json` sudah dibuat di Apps Script project
- [ ] File `appsscript.json` berisi 2 oauthScopes (spreadsheets + external_request)
- [ ] Authorization lama sudah di-remove dari Google Account
- [ ] Re-authorization berhasil (tidak ada error "permissions not sufficient")
- [ ] Ada 2 trigger: onEditTracking dan onChangeTracking
- [ ] Edit cell di spreadsheet → data muncul di dashboard (dalam 2-3 detik)
- [ ] Tidak ada error di Executions log (icon ⚡)
- [ ] Kolom Account terisi untuk data baru (bukan "-")
- [ ] Nomor WA format 62xxx

---

## ⚠️ PENTING - Bedanya dengan Error Sebelumnya:

| Error | Script | Permission | Solusi |
|-------|--------|------------|--------|
| MailApp.sendEmail | kirimWhatsApp.gs | send_mail | Disable email / Re-auth |
| **UrlFetchApp.fetch** | **tracking-script.gs** | **external_request** | **Buat appsscript.json** |

**Ini error BERBEDA!** Karena tracking script butuh permission **external_request** untuk kirim data ke API eksternal.

---

## 📁 File appsscript.json - Apa Itu?

File ini adalah **manifest** yang memberitahu Google:
- Permission apa saja yang dibutuhkan script
- Timezone yang digunakan
- Runtime version (V8 engine)

**Kenapa harus dibuat manual?**
- Kalau tidak ada, Google pakai **auto-detect** permission
- Auto-detect kadang **tidak lengkap** atau **salah**
- Dengan manual, kita **eksplisit** tentukan permission yang benar

---

## ✅ Selesai!

Setelah semua langkah:
- ✅ Error UrlFetchApp permission sudah fixed
- ✅ Tracking berfungsi normal
- ✅ Data masuk ke dashboard dengan benar
- ✅ Kolom Account terisi
- ✅ Nomor WA format 62xxx
