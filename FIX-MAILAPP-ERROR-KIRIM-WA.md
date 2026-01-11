# 🔧 Fix MailApp Error di Script kirimWhatsApp()

## Error:
```
Exception: Specified permissions are not sufficient to call MailApp.sendEmail.
Required permissions: https://www.googleapis.com/auth/script.send_mail
```

## Penyebab:
Script menggunakan `MailApp.sendEmail()` di fungsi `sendConsolidatedEmail()` tapi permission sudah dihapus/tidak cukup.

---

## ✅ PILIH SALAH SATU SOLUSI:

### **Solusi A: Matikan Email Notification** (Tidak Butuh Email)

Jika kamu **TIDAK BUTUH** notifikasi email, cukup **disable** fungsi emailnya:

#### **Langkah 1: Comment Baris Email**

Di fungsi `kirimWhatsApp()`, cari baris ini:
```javascript
// Send consolidated email
sendConsolidatedEmail(logs);
```

**Ubah jadi:**
```javascript
// ❌ DISABLED: Send consolidated email (uncomment jika butuh email lagi)
// sendConsolidatedEmail(logs);

// ✅ ALTERNATIVE: Log to console instead
Logger.log("=== SUMMARY LOGS ===");
Logger.log(logs.clientProsesLog);
Logger.log(logs.clientReminderH3Log);
Logger.log(logs.clientReminderH2Log);
Logger.log(logs.clientReminderH1Log);
Logger.log(logs.clientJatuhTempoLog);
Logger.log(logs.clientWarningLog);
if (logs.errorLog) Logger.log("ERRORS:\n" + logs.errorLog);
```

#### **Langkah 2: Save & Test**
1. **Save** script (Ctrl+S)
2. **Run** fungsi `kirimWhatsApp()`
3. Cek **Logs** (View → Logs) untuk lihat summary
4. ✅ **Selesai!** Tidak ada error lagi

---

### **Solusi B: Enable Email dengan Re-Authorization** (Masih Butuh Email)

Jika kamu **MASIH BUTUH** notifikasi email, lakukan re-authorization:

#### **Langkah 1: Buat File appsscript.json**

1. Di Apps Script Editor
2. Klik **+** (Add a file) → pilih **Script**
3. Ganti nama jadi `appsscript.json`
4. Paste kode ini:

```json
{
  "timeZone": "Asia/Jakarta",
  "dependencies": {},
  "exceptionLogging": "STACKDRIVER",
  "oauthScopes": [
    "https://www.googleapis.com/auth/spreadsheets",
    "https://www.googleapis.com/auth/script.send_mail"
  ]
}
```

#### **Langkah 2: Reset Authorization**

1. Buka: https://myaccount.google.com/permissions
2. Cari project Apps Script Anda
3. Klik **Remove Access**
4. Tutup tab

#### **Langkah 3: Re-Authorize Script**

1. Kembali ke Apps Script Editor
2. Pilih fungsi **`kirimWhatsApp`** di dropdown
3. Klik **Run** (▶️)
4. Akan muncul **Authorization Required**
5. Klik **Review Permissions**
6. Pilih akun Google
7. Klik **Advanced** → **Go to [Project Name] (unsafe)**
8. **Scroll down** → Centang semua permission termasuk:
   - ✅ Send email as you
   - ✅ See, edit, create, and delete all your Google Sheets
9. Klik **Allow**

#### **Langkah 4: Test Email**

1. Run `kirimWhatsApp()` lagi
2. Cek inbox email → harusnya ada email summary
3. ✅ **Selesai!** Email notification aktif

---

## 📋 Comparison: Mana Yang Lebih Baik?

| Aspek | Solusi A (Disable Email) | Solusi B (Enable Email) |
|-------|-------------------------|------------------------|
| **Kecepatan Fix** | ⚡ 1 menit | 🕐 5 menit |
| **Kompleksitas** | ✅ Mudah | ⚠️ Sedang |
| **Email Summary** | ❌ Tidak ada | ✅ Ada via email |
| **Console Logs** | ✅ Ada di Logs | ✅ Ada di Logs |
| **Keamanan** | ✅ Less permission | ⚠️ More permission |
| **Maintenance** | ✅ Lebih simple | ⚠️ Need monitoring |

**Rekomendasi:**
- Pilih **Solusi A** kalau: Cuma butuh lihat logs di console, tidak perlu email
- Pilih **Solusi B** kalau: Butuh email notification otomatis ke inbox

---

## 🔍 Alternative: Send to Google Chat/Slack

Kalau tidak suka email tapi masih butuh notification:

### **Option 1: Google Chat Webhook**
```javascript
function sendToGoogleChat(logs) {
  var webhookUrl = "https://chat.googleapis.com/v1/spaces/xxx/messages?key=xxx&token=xxx";

  var message = {
    text: logs.clientProsesLog + "\n\n" + logs.clientReminderH3Log
  };

  UrlFetchApp.fetch(webhookUrl, {
    method: 'post',
    contentType: 'application/json',
    payload: JSON.stringify(message)
  });
}

// Replace line:
// sendConsolidatedEmail(logs);
// With:
sendToGoogleChat(logs);
```

### **Option 2: Slack Webhook**
```javascript
function sendToSlack(logs) {
  var webhookUrl = "https://hooks.slack.com/services/YOUR/WEBHOOK/URL";

  var message = {
    text: "*CLIENT SUMMARY*",
    blocks: [
      {
        type: "section",
        text: {
          type: "mrkdwn",
          text: logs.clientProsesLog
        }
      }
    ]
  };

  UrlFetchApp.fetch(webhookUrl, {
    method: 'post',
    contentType: 'application/json',
    payload: JSON.stringify(message)
  });
}
```

---

## ✅ Checklist Setelah Fix:

**Untuk Solusi A (Disable Email):**
- [ ] Baris `sendConsolidatedEmail(logs)` sudah di-comment
- [ ] Logger.log sudah ditambahkan sebagai alternative
- [ ] Script di-save
- [ ] Test run `kirimWhatsApp()` → tidak ada error
- [ ] Cek Logs (View → Logs) → summary muncul

**Untuk Solusi B (Enable Email):**
- [ ] File appsscript.json sudah dibuat dengan oauthScopes
- [ ] Authorization lama sudah di-remove
- [ ] Re-authorization berhasil (tidak ada error permission)
- [ ] Test run `kirimWhatsApp()` → tidak ada error
- [ ] Email summary masuk ke inbox

---

## 🆘 Troubleshooting

### "Authorization required" tidak muncul
→ Clear cache browser, logout Google, login lagi, coba run function

### Email tidak masuk tapi tidak ada error
→ Cek Spam folder, atau cek `MailApp.getRemainingDailyQuota()`

### Error "Service invoked too many times"
→ Ada quota limit, tunggu 24 jam atau kurangi frekuensi email

---

**File Fixed:** `kirimWhatsApp-FIXED.gs` (sudah disable email, tinggal copy-paste)
