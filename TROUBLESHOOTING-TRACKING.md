# 🔧 Troubleshooting: Editor Baru Tidak Ter-record

## Masalah
Email yang ditambahkan sebagai editor ke Google Sheet tidak ter-record saat melakukan perubahan.

## Error yang Sering Muncul

### Error 1: User Email Kosong
```
Log menunjukkan:
  User:

API response:
  "Missing required fields: user_email"
```

**Penyebab:** Trigger tidak memiliki authorization yang cukup untuk membaca email user.

**Solusi:** Re-authorize trigger (lihat LANGKAH 4 di bawah).

## Penyebab Utama

### 1. Trigger Tidak Diinstall Dengan Benar
Google Apps Script memiliki 2 jenis trigger:

- **Simple Trigger** (onEdit): Hanya berfungsi untuk owner script
- **Installable Trigger**: Berfungsi untuk SEMUA editor ✅

## Solusi Lengkap

### LANGKAH 1: Cek Trigger Yang Sudah Terpasang

1. Buka Google Sheet Anda
2. Klik **Extensions > Apps Script**
3. Di Apps Script Editor, klik icon **⏰ Triggers** (icon jam) di sidebar kiri
4. Lihat daftar trigger yang sudah terpasang

**Yang BENAR** harus ada 2 trigger:
```
Function: onEditTracking
Event type: On edit
```
dan
```
Function: onChangeTracking
Event type: On change
```

### LANGKAH 2: Hapus Trigger Lama (Jika Ada Yang Salah)

Jika trigger Anda terlihat seperti ini (SALAH):
```
Function: onEdit (tanpa "Tracking")
```

Maka Anda perlu:
1. Klik titik tiga ⋮ di sebelah kanan trigger tersebut
2. Pilih **Delete**
3. Hapus semua trigger yang salah

### LANGKAH 3: Install Trigger Yang Benar

1. Di Apps Script Editor, klik icon **⏰ Triggers**
2. Klik tombol **+ Add Trigger** (kanan bawah)

**TRIGGER 1 - Track Cell Edit:**
- Choose which function to run: **onEditTracking**
- Choose which deployment should run: **Head**
- Select event source: **From spreadsheet**
- Select event type: **On edit**
- Klik **Save**

3. Klik **+ Add Trigger** lagi untuk trigger kedua

**TRIGGER 2 - Track Insert/Delete Row:**
- Choose which function to run: **onChangeTracking**
- Choose which deployment should run: **Head**
- Select event source: **From spreadsheet**
- Select event type: **On change**
- Klik **Save**

### LANGKAH 4: Authorize Script (PENTING! - INI YANG PALING SERING DILUPAKAN!)

Saat Anda menyimpan trigger, Google akan meminta authorization:

1. Popup pertama akan muncul → Klik **Continue**
2. Pilih **akun Google Anda** (yang punya akses ke spreadsheet)
3. Mungkin muncul warning "Google hasn't verified this app" → Klik **Advanced**
4. Klik **Go to [Project Name] (unsafe)**
5. **PENTING:** Review permissions yang diminta:
   - ✅ "See, edit, create, and delete all your Google Sheets spreadsheets"
   - ✅ "Connect to an external service"
   - ✅ "Allow this application to run when you are not present"
   - ✅ "See your primary Google Account email address"
6. Scroll ke bawah → Klik **Allow**

**CATATAN:**
- Ini normal untuk custom script, bukan berarti berbahaya
- Tanpa authorization penuh, `Session.getActiveUser().getEmail()` akan return empty string
- Jika Anda skip step ini atau klik "Deny", tracking TIDAK akan berfungsi untuk editor lain

### LANGKAH 4B: Cek Authorization Status (VALIDASI!)

Untuk memastikan authorization berhasil:

1. Di Apps Script Editor, klik **Project Settings** (⚙️ icon di sidebar)
2. Scroll ke bagian **OAuth Scopes**
3. Pastikan ada scope ini:
   ```
   https://www.googleapis.com/auth/spreadsheets
   https://www.googleapis.com/auth/script.external_request
   https://www.googleapis.com/auth/userinfo.email
   ```
4. Jika tidak ada scope `userinfo.email`, authorization Anda tidak lengkap!

**Cara Fix:**
- Hapus semua trigger
- Install ulang trigger
- Re-authorize dengan **ALLOW semua permissions**

### LANGKAH 5: Test Tracking

1. Minta editor untuk edit sebuah cell
2. Tunggu 5-10 detik
3. Refresh halaman dashboard tracking Anda (https://star.octolink.id)
4. Cek apakah perubahan ter-record

## Troubleshooting Lanjutan

### Cek Execution Log di Apps Script

1. Buka **Extensions > Apps Script**
2. Klik **Executions** (icon list di sidebar kiri)
3. Lihat log executions terakhir
4. Jika ada error, klik untuk melihat detail

### Cek Fallback Log di Sheet

Script otomatis menyimpan error ke sheet backup:
1. Cek apakah ada sheet bernama **_Tracking_Log**
2. Jika ada, lihat kolom "API Error" untuk error message
3. Error umum:
   - "User email is empty" = Trigger tidak authorized dengan benar
   - "Failed to send to API" = Masalah koneksi atau API URL salah

### Pastikan API URL Sudah Benar

1. Buka **Extensions > Apps Script**
2. Cek baris 12 di file `tracking-script.gs`:
```javascript
const TRACKING_API_URL = 'https://your-domain.com/api/track-change.php';
```

3. Pastikan URL-nya benar (BUKAN `your-domain.com`!)
4. Seharusnya: `https://star.octolink.id/api/track-change.php`

## Kenapa Session.getActiveUser() Tidak Berfungsi?

**Simple Trigger:**
```javascript
function onEdit(e) {  // ❌ Tidak bisa ambil email editor lain
  var user = Session.getActiveUser().getEmail(); // Returns empty string!
}
```

**Installable Trigger:**
```javascript
function onEditTracking(e) {  // ✅ Bisa ambil email semua editor
  var user = Session.getActiveUser().getEmail(); // Returns actual email!
}
```

## Checklist Akhir

- [ ] Trigger `onEditTracking` sudah terpasang sebagai "On edit"
- [ ] Trigger `onChangeTracking` sudah terpasang sebagai "On change"
- [ ] Script sudah di-authorize (tidak ada warning authorization)
- [ ] API URL sudah benar (`https://star.octolink.id/api/track-change.php`)
- [ ] Test edit oleh editor baru berhasil ter-record

## Test Manual

Jalankan test manual untuk memastikan API berfungsi:

1. Di Apps Script Editor, pilih function **testTracking** dari dropdown
2. Klik **Run** (▶️)
3. Cek log output (View > Logs)
4. Cek apakah data masuk ke database

## Masih Bermasalah?

Jika masih tidak berfungsi setelah langkah di atas:

1. **Screenshot trigger yang terpasang** - kirim screenshot halaman Triggers
2. **Cek execution log** - screenshot error di Executions
3. **Cek _Tracking_Log sheet** - screenshot jika ada error
4. **Test dengan owner** - pastikan owner bisa ter-record, baru test editor lain

## Dokumentasi Resmi

- [Google Apps Script Triggers](https://developers.google.com/apps-script/guides/triggers)
- [Simple vs Installable Triggers](https://developers.google.com/apps-script/guides/triggers/installable)
