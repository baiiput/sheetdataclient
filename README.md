# 📊 Google Sheet Change Tracking System

Sistem monitoring perubahan Google Sheet secara real-time dengan web dashboard yang responsive dan mobile-friendly dengan dark mode support.

## ✨ Fitur

- ✅ **Tracking Real-time**: Mencatat semua perubahan di Google Sheet secara otomatis
- ✅ **Informasi Lengkap**: Siapa, kapan, dimana, nilai lama & baru
- ✅ **Web Dashboard**: Interface yang clean, readable, dan mobile-friendly
- ✅ **Dark Mode**: Support dark mode untuk kenyamanan mata
- ✅ **Filter & Search**: Filter berdasarkan tanggal, user, sheet, dan search
- ✅ **Export CSV**: Export data tracking ke format CSV
- ✅ **Responsive**: Tampilan optimal di desktop, tablet, dan mobile
- ✅ **Fallback System**: Jika API gagal, data tetap tersimpan di Sheet log

## 📁 Struktur File

```
sheetdataclient/
├── api/
│   └── track-change.php          # API endpoint untuk menerima data tracking
├── includes/
│   ├── config.php                # Konfigurasi sistem
│   ├── db.php                    # Database connection class
│   └── functions.php             # Helper functions
├── assets/
│   ├── css/
│   │   └── style.css            # Styling dengan dark mode
│   └── js/
│       └── script.js            # JavaScript interaktivity
├── database.sql                  # Schema database MySQL
├── tracking-script.gs            # Google Apps Script (tracking)
├── login.php                     # Halaman login
├── index.php                     # Dashboard utama
└── README.md                     # Dokumentasi ini
```

## 🚀 Instalasi

### 1️⃣ Setup Database

**a. Buat Database di VPS**

Login ke phpMyAdmin atau MySQL console di aaPanel:

```bash
mysql -u root -p
```

**b. Import Schema Database**

```bash
mysql -u root -p < database.sql
```

Atau via phpMyAdmin: Import file `database.sql`

**c. Buat User Database (Recommended)**

```sql
CREATE USER 'sheet_tracker'@'localhost' IDENTIFIED BY 'password_kuat_anda';
GRANT ALL PRIVILEGES ON sheet_tracking.* TO 'sheet_tracker'@'localhost';
FLUSH PRIVILEGES;
```

**d. Verifikasi Database**

```sql
USE sheet_tracking;
SHOW TABLES;
-- Harus ada: change_logs, users, admin_users, daily_statistics
```

### 2️⃣ Setup Web Dashboard di VPS

**a. Upload File ke VPS**

Upload semua file ke VPS Anda melalui:
- FTP/SFTP
- File Manager di aaPanel
- Git clone (jika menggunakan repository)

Contoh lokasi: `/www/wwwroot/tracking.yourdomain.com/`

**b. Konfigurasi Database**

Edit file `includes/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sheet_tracking');
define('DB_USER', 'sheet_tracker');        // Username database Anda
define('DB_PASS', 'password_kuat_anda');   // Password database Anda
```

**c. Set Permission**

```bash
chmod 755 /www/wwwroot/tracking.yourdomain.com
chmod 644 /www/wwwroot/tracking.yourdomain.com/*.php
chmod 644 /www/wwwroot/tracking.yourdomain.com/includes/*.php
chmod 644 /www/wwwroot/tracking.yourdomain.com/api/*.php
mkdir -p /www/wwwroot/tracking.yourdomain.com/logs
chmod 775 /www/wwwroot/tracking.yourdomain.com/logs
```

**d. Setup Virtual Host di aaPanel**

1. Buka aaPanel
2. Website → Add site
3. Domain: `tracking.yourdomain.com`
4. Path: `/www/wwwroot/tracking.yourdomain.com`
5. PHP Version: 7.4 atau lebih tinggi
6. Save

**e. Test Dashboard**

Buka browser: `https://tracking.yourdomain.com/login.php`

Default login:
- Username: `admin`
- Password: `admin123`

⚠️ **PENTING: Ganti password default setelah login pertama!**

### 3️⃣ Setup Google Apps Script

**a. Buka Google Sheet Anda**

Buka Google Sheet yang ingin di-tracking

**b. Buka Apps Script Editor**

Extensions → Apps Script

**c. Tambah Script Tracking**

1. Klik tombol `+` untuk membuat file baru
2. Beri nama: `TrackingSystem`
3. Copy-paste isi file `tracking-script.gs` ke editor
4. **PENTING**: Ganti URL API di baris 11:

```javascript
const TRACKING_API_URL = 'https://tracking.yourdomain.com/api/track-change.php';
```

Ganti `tracking.yourdomain.com` dengan domain VPS Anda!

**d. (Opsional) Konfigurasi Sheet yang di-Track**

Jika ingin tracking hanya sheet tertentu, edit baris 14:

```javascript
// Kosong = track semua sheet
const TRACKED_SHEETS = [];

// Atau specify sheet tertentu:
const TRACKED_SHEETS = ['Client Aktif', 'Client Lepas', 'Client Non Aktif'];
```

**e. Install Trigger**

1. Di Apps Script Editor, klik icon **⏰ (Triggers)** di sidebar kiri
2. Klik **+ Add Trigger** di kanan bawah
3. Konfigurasi:
   - Function: `onEditTracking`
   - Event source: `From spreadsheet`
   - Event type: `On edit`
4. Klik **Save**
5. Authorize script (klik Review Permissions → pilih akun Google → Allow)

**f. Test Tracking**

1. Di Apps Script Editor, pilih function `testTracking` dari dropdown
2. Klik **Run**
3. Lihat log (View → Logs) untuk memastikan data terkirim
4. Cek dashboard web untuk melihat test data

**g. Test di Sheet**

1. Edit salah satu cell di Google Sheet
2. Tunggu beberapa detik
3. Refresh dashboard web
4. Perubahan harus muncul di dashboard

## 🎯 Cara Penggunaan

### Login ke Dashboard

1. Buka: `https://tracking.yourdomain.com/login.php`
2. Login dengan username & password
3. Anda akan diarahkan ke dashboard

### Filter Data

- **Search**: Cari berdasarkan nama client, KIT number, atau cell
- **Sheet**: Filter berdasarkan sheet tertentu
- **User**: Filter berdasarkan user yang melakukan perubahan
- **Tanggal**: Filter berdasarkan range tanggal
- **Tampilan**: Ubah jumlah data per halaman

### Export Data

1. Set filter sesuai kebutuhan
2. Klik tombol **📥 Export CSV**
3. File CSV akan terdownload otomatis

### Keyboard Shortcuts

- `Ctrl+K` atau `Cmd+K`: Focus ke search box
- `Ctrl+T` atau `Cmd+T`: Toggle dark/light mode
- `Escape`: Clear search box

## 🔧 Troubleshooting

### Data tidak masuk ke database

**Cek Apps Script Log:**
1. Buka Apps Script Editor
2. View → Logs
3. Lihat error message

**Cek API Endpoint:**
1. Buka `https://tracking.yourdomain.com/api/track-change.php` di browser
2. Seharusnya muncul error "Method not allowed" (ini normal)
3. Jika error 500/404, cek konfigurasi web server

**Cek Fallback Log:**
1. Buka Google Sheet
2. Cek apakah ada sheet `_Tracking_Log`
3. Jika ada, berarti Apps Script berjalan tapi API gagal
4. Cek URL API di `tracking-script.gs`

### Dashboard tidak bisa login

**Reset password admin:**

```sql
USE sheet_tracking;
UPDATE admin_users
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE username = 'admin';
```

Password akan direset ke `admin123`

### Error "Database connection failed"

1. Cek kredensial database di `includes/config.php`
2. Pastikan database sudah dibuat
3. Pastikan user database punya permission
4. Test koneksi:

```bash
mysql -u sheet_tracker -p sheet_tracking
```

### Perubahan tidak tercatat

1. Pastikan trigger sudah terinstall (cek di ⏰ Triggers)
2. Pastikan sheet yang diedit ada di `TRACKED_SHEETS` (atau kosongkan untuk track semua)
3. Cek Apps Script execution log
4. Pastikan user yang edit punya akses ke Google Sheet

## 📊 Maintenance

### Hapus Log Lama

Untuk menghemat space database, jalankan:

```sql
USE sheet_tracking;
CALL cleanup_old_logs(365); -- Hapus log > 1 tahun
```

Atau buat cron job di aaPanel:

```bash
0 2 * * 0 mysql -u sheet_tracker -p'password' sheet_tracking -e "CALL cleanup_old_logs(365);"
```

### Backup Database

```bash
mysqldump -u sheet_tracker -p sheet_tracking > backup_$(date +%Y%m%d).sql
```

### Monitor Log File

```bash
tail -f /www/wwwroot/tracking.yourdomain.com/logs/app.log
```

## 🔒 Security Tips

1. **Ganti password default** admin segera setelah install
2. **Gunakan HTTPS** untuk dashboard (install SSL di aaPanel)
3. **Restrict IP** jika memungkinkan (di aaPanel firewall)
4. **Backup database** secara berkala
5. **Update PHP** ke versi terbaru
6. **Set API_KEY** di `includes/config.php` untuk keamanan tambahan

### Mengaktifkan API Key (Opsional)

**File: `includes/config.php`**
```php
define('API_REQUIRE_AUTH', true);
define('API_KEY', 'generate-random-string-panjang-disini');
```

**File: `tracking-script.gs`**
```javascript
function sendToTrackingAPI(data) {
  var options = {
    'method': 'post',
    'contentType': 'application/json',
    'payload': JSON.stringify(data),
    'headers': {
      'X-API-Key': 'your-api-key-here'  // Tambahkan ini
    },
    'muteHttpExceptions': true
  };
  // ... rest of code
}
```

## 📱 Mobile Friendly

Dashboard sudah dioptimasi untuk mobile:
- Responsive layout
- Touch-friendly buttons
- Optimized table scrolling
- Mobile-friendly filters

## 🎨 Customization

### Mengubah Theme Default

Edit `includes/config.php`:

```php
define('DEFAULT_THEME', 'light'); // atau 'dark'
```

### Mengubah Jumlah Data per Page

Edit `includes/config.php`:

```php
define('RECORDS_PER_PAGE', 100); // default 50
define('PAGE_SIZE_OPTIONS', [50, 100, 200, 500]);
```

### Menambah Admin Baru

```sql
INSERT INTO admin_users (username, password, email, name)
VALUES ('newadmin', PASSWORD_HASH_HERE, 'email@example.com', 'Nama Admin');
```

Generate password hash:
```php
<?php echo password_hash('password_anda', PASSWORD_DEFAULT); ?>
```

## 📞 Support

Jika ada pertanyaan atau masalah:
1. Cek troubleshooting guide di atas
2. Cek Apps Script logs
3. Cek server error logs di aaPanel
4. Cek database connection

## 📄 License

Free to use untuk keperluan pribadi dan komersial.

## 🎉 Selamat!

Sistem tracking sudah siap digunakan. Setiap perubahan di Google Sheet akan tercatat secara otomatis dan bisa dimonitor melalui dashboard web.

**Happy Tracking! 📊**
