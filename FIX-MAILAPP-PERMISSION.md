# 🔧 Fix: MailApp Permission Error

## Error Yang Terjadi:
```
Exception: Specified permissions are not sufficient to call MailApp.sendEmail.
Required permissions: https://www.googleapis.com/auth/script.send_mail
```

## Penyebab:
Error ini terjadi karena **trigger lama atau manifest file** masih meminta permission `MailApp.sendEmail` padahal tracking script saat ini **TIDAK** menggunakan MailApp sama sekali.

---

## ✅ Solusi: Hapus Trigger Lama & Install Ulang

### **STEP 1: Hapus Semua Trigger Lama**

1. Buka **Google Sheets** Anda
2. Klik **Extensions → Apps Script**
3. Di sidebar kiri, klik icon **⏰ Triggers** (jam/alarm)
4. **Hapus SEMUA trigger** yang ada:
   - Klik tombol **⋮** (titik tiga) di sebelah kanan setiap trigger
   - Pilih **Delete trigger**
   - Ulangi sampai tidak ada trigger tersisa

### **STEP 2: Hapus File Manifest (Jika Ada)**

1. Masih di Apps Script Editor
2. Di sidebar kiri, cari file **appsscript.json**
3. Jika ada, **HAPUS** file ini (klik ⋮ → Remove file)
4. Atau jika tidak bisa dihapus, **edit** isinya menjadi:

```json
{
  "timeZone": "Asia/Jakarta",
  "dependencies": {},
  "exceptionLogging": "STACKDRIVER"
}
```

### **STEP 3: Verifikasi Tracking Script**

1. Pastikan file utama berisi **tracking-script.gs** yang terbaru
2. Pastikan **TIDAK ADA** kode yang menggunakan:
   - `MailApp.sendEmail()`
   - `GmailApp.sendEmail()`
   - Atau fungsi email lainnya

### **STEP 4: Install Trigger Baru**

1. Di Apps Script Editor, pilih fungsi **`onEditTracking`** di dropdown (di toolbar)
2. Klik **Run** (▶️)
3. Akan muncul **Authorization Required** → Klik **Review Permissions**
4. Pilih akun Google Anda
5. Klik **Advanced** → **Go to [Project Name] (unsafe)**
6. Klik **Allow**

### **STEP 5: Buat Installable Triggers**

Setelah authorization berhasil:

1. Klik icon **⏰ Triggers** di sidebar kiri
2. Klik **+ Add Trigger** (kanan bawah)

**Trigger 1: onEdit**
- Choose which function to run: **`onEditTracking`**
- Choose which deployment: **Head**
- Select event source: **From spreadsheet**
- Select event type: **On edit**
- Click **Save**

**Trigger 2: onChange**
- Klik **+ Add Trigger** lagi
- Choose which function to run: **`onChangeTracking`**
- Choose which deployment: **Head**
- Select event source: **From spreadsheet**
- Select event type: **On change**
- Click **Save**

### **STEP 6: Test Tracking**

1. Edit cell di spreadsheet (ubah nilai apa saja)
2. Tunggu 2-3 detik
3. Cek dashboard tracking → Data baru harus muncul
4. Jika ada error, cek **Executions** (icon ⚡ di sidebar)

---

## 🔍 Troubleshooting

### Jika Masih Error "Permission not sufficient":

**Opsi A: Reset Authorization**
```
1. Buka: https://myaccount.google.com/permissions
2. Cari project Apps Script Anda
3. Klik "Remove Access"
4. Ulangi STEP 4 (Install Trigger Baru)
```

**Opsi B: Buat Project Baru**
```
1. Buka Google Sheets
2. Extensions → Apps Script
3. Buat project baru
4. Copy paste tracking-script.gs
5. Install trigger seperti STEP 5
```

### Jika Error "Script function not found":
- Pastikan nama fungsi di trigger: **`onEditTracking`** (bukan `onEdit`)
- Pastikan nama fungsi di trigger: **`onChangeTracking`** (bukan `onChange`)

### Jika Data Tidak Masuk Dashboard:
```
1. Cek Executions (icon ⚡) untuk lihat error log
2. Pastikan TRACKING_API_URL benar:
   https://star.octolink.id/api/track-change.php
3. Test manual: Run function testTracking()
4. Cek Network tab di browser console
```

---

## 📋 Checklist Verification

Setelah selesai, pastikan:

- [ ] Semua trigger lama sudah dihapus
- [ ] appsscript.json tidak meminta MailApp permission
- [ ] Authorization berhasil (tidak ada error permission)
- [ ] Ada 2 trigger: onEditTracking dan onChangeTracking
- [ ] Edit cell di spreadsheet → data muncul di dashboard
- [ ] Kolom Account terisi (bukan "-") untuk data baru
- [ ] Nomor WA terisi dengan format 62xxx
- [ ] Tidak ada error di Executions log

---

## ✅ Selesai!

Setelah semua langkah di atas:
- ✅ Error MailApp permission sudah fixed
- ✅ Tracking berfungsi normal
- ✅ Data masuk ke dashboard dengan benar
