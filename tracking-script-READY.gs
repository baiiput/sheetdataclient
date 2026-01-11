// ========================================
// 🚀 STARLINK MANAGEMENT SYSTEM - CHANGE TRACKING
// ========================================
// Script untuk tracking semua perubahan di Google Sheet
// FIXED: Menggunakan getDisplayValue() untuk tanggal

// ========================================
// 🔧 KONFIGURASI
// ========================================

// URL API endpoint di VPS Anda (SUDAH DIGANTI!)
const TRACKING_API_URL = 'https://star.octolink.id/api/track-change.php';

// List sheet yang ingin ditrack (kosongkan array untuk track semua sheet)
const TRACKED_SHEETS = []; // Contoh: ['Client Aktif', 'Client Lepas', 'Client Non Aktif']

// ========================================
// 📝 FUNGSI UTAMA - ON EDIT TRIGGER
// ========================================

/**
 * Fungsi ini otomatis dipanggil setiap kali ada perubahan di sheet
 * CARA INSTALL TRIGGER:
 * 1. Buka Apps Script Editor
 * 2. Klik icon jam (Triggers) di sidebar kiri
 * 3. Klik "+ Add Trigger" di kanan bawah
 *
 * TRIGGER 1 - Track Edit (UPDATE/INSERT/DELETE cell):
 * - Pilih fungsi: onEditTracking
 * - Event type: On edit
 * - Klik Save
 *
 * TRIGGER 2 - Track Insert/Delete Row:
 * - Klik "+ Add Trigger" lagi
 * - Pilih fungsi: onChangeTracking
 * - Event type: On change
 * - Klik Save
 */
function onEditTracking(e) {
  try {
    // Validasi event
    if (!e || !e.range) {
      Logger.log('⚠️ Event tidak valid atau tidak ada range yang diubah');
      return;
    }

    var sheet = e.range.getSheet();
    var sheetName = sheet.getName();

    // Skip jika sheet tidak ingin ditrack
    if (TRACKED_SHEETS.length > 0 && TRACKED_SHEETS.indexOf(sheetName) === -1) {
      Logger.log('⏭️ Sheet "' + sheetName + '" tidak ditrack (tidak ada di TRACKED_SHEETS)');
      return;
    }

    // Skip sheet sistem atau template
    if (sheetName.startsWith('_') || sheetName === 'Template' || sheetName === 'Config') {
      Logger.log('⏭️ Skip system sheet: ' + sheetName);
      return;
    }

    // Get user email with fallback methods
    // Priority 1: Try to get from event object (only works for Workspace accounts)
    var user = '';

    try {
      if (e.user && e.user.email) {
        user = e.user.email;
        Logger.log('✅ Got user email from event object: ' + user);
      }
    } catch (err) {
      Logger.log('⚠️ e.user not available (normal for personal Gmail)');
    }

    // Priority 2: Try Session methods
    if (!user || user === '') {
      user = getUserEmail();
    }

    // Jika masih kosong, gunakan fallback identifier
    if (!user || user === '') {
      Logger.log('⚠️ Cannot get user email - using fallback');
      Logger.log('💡 Limitation: Personal Gmail tidak support user tracking');
      user = 'unknown-editor';
    }

    var timestamp = new Date();
    var row = e.range.getRow();
    var column = e.range.getColumn();
    var columnName = getColumnLetter(column);

    // ========================================
    // 🔧 SIMPLE FIX: Gunakan getDisplayValue()
    // ========================================

    var oldValue = '';
    var newValue = '';

    // NEW VALUE: Gunakan getDisplayValue() - ini otomatis format tanggal!
    try {
      var displayValue = e.range.getDisplayValue();
      if (displayValue && displayValue !== '') {
        newValue = displayValue;
      } else if (e.value !== undefined && e.value !== null) {
        newValue = e.value.toString();
      }
    } catch (err) {
      newValue = (e.value || '').toString();
    }

    // OLD VALUE: Format manual karena tidak ada getDisplayValue() untuk old value
    if (e.oldValue !== undefined && e.oldValue !== null && e.oldValue !== '') {
      // Convert to number first (handle string numbers like "46025.0")
      var numValue = typeof e.oldValue === 'number' ? e.oldValue : parseFloat(e.oldValue);

      // Cek apakah ini kemungkinan tanggal (serial number)
      if (!isNaN(numValue) && numValue > 1000 && numValue < 100000) {
        // Kemungkinan besar ini tanggal serial number
        try {
          // Konversi serial number ke tanggal (hilangkan decimal dengan Math.floor)
          var serialDate = new Date(Math.round((Math.floor(numValue) - 25569) * 86400 * 1000));

          // Validasi: cek apakah hasil konversi masuk akal (tahun antara 1900-2100)
          if (serialDate.getFullYear() >= 1900 && serialDate.getFullYear() <= 2100) {
            oldValue = Utilities.formatDate(serialDate, 'Asia/Jakarta', 'dd/MM/yyyy');
          } else {
            // Bukan tanggal, convert ke string biasa
            oldValue = e.oldValue.toString();
          }
        } catch (dateErr) {
          Logger.log('⚠️ Date conversion error: ' + dateErr.message);
          oldValue = e.oldValue.toString();
        }
      } else {
        // Bukan number atau di luar range tanggal, convert langsung
        oldValue = e.oldValue.toString();
      }
    }

    // Jika nilai sama, skip (tidak ada perubahan)
    if (oldValue === newValue) {
      Logger.log('⏭️ Nilai tidak berubah, skip tracking');
      return;
    }

    // Deteksi action type
    var actionType = 'UPDATE'; // default
    if ((oldValue === '' || oldValue === null) && newValue !== '') {
      actionType = 'INSERT';
    } else if ((newValue === '' || newValue === null) && oldValue !== '') {
      actionType = 'DELETE';
    }

    // Ambil informasi tambahan dari row yang diubah
    var rowData = sheet.getRange(row, 1, 1, sheet.getLastColumn()).getValues()[0];
    var clientName = formatValue(rowData[0]); // Kolom A: Nama
    var kitNumber = formatValue(rowData[8]); // Kolom I: KIT Number

    // Buat objek data tracking
    var trackingData = {
      spreadsheet_id: SpreadsheetApp.getActiveSpreadsheet().getId(),
      spreadsheet_name: SpreadsheetApp.getActiveSpreadsheet().getName(),
      sheet_name: sheetName,
      user_email: user,
      timestamp: Utilities.formatDate(timestamp, 'Asia/Jakarta', 'yyyy-MM-dd HH:mm:ss'),
      row_number: row,
      column_number: column,
      column_name: columnName,
      old_value: oldValue.substring(0, 1000), // Limit 1000 karakter
      new_value: newValue.substring(0, 1000),
      client_name: clientName.substring(0, 255),
      kit_number: kitNumber.substring(0, 100),
      action_type: actionType
    };

    Logger.log('📊 Tracking change:');
    Logger.log('  Sheet: ' + sheetName);
    Logger.log('  User: ' + user);
    Logger.log('  Cell: ' + columnName + row);
    Logger.log('  Old: ' + oldValue);
    Logger.log('  New: ' + newValue);
    Logger.log('  Type: ' + actionType);

    // Kirim ke API
    sendToTrackingAPI(trackingData);

  } catch (error) {
    Logger.log('❌ Error in onEditTracking: ' + error.message);
    Logger.log('Stack: ' + error.stack);
    // Jangan throw error agar tidak mengganggu user edit
  }
}

// ========================================
// 📝 FUNGSI ON CHANGE - TRACK INSERT/DELETE ROW
// ========================================

/**
 * Fungsi untuk tracking insert/delete row
 * Fungsi ini akan dipanggil setiap ada perubahan struktur sheet
 */
function onChangeTracking(e) {
  try {
    // Validasi event
    if (!e) {
      Logger.log('⚠️ Event tidak valid');
      return;
    }

    Logger.log('📋 onChange Event detected:');
    Logger.log('  Type: ' + e.changeType);

    // Hanya track INSERT_ROW dan REMOVE_ROW
    if (e.changeType !== 'INSERT_ROW' && e.changeType !== 'REMOVE_ROW') {
      Logger.log('⏭️ Skip - bukan insert/delete row');
      return;
    }

    var sheet = SpreadsheetApp.getActiveSheet();
    var sheetName = sheet.getName();

    // Skip jika sheet tidak ingin ditrack
    if (TRACKED_SHEETS.length > 0 && TRACKED_SHEETS.indexOf(sheetName) === -1) {
      Logger.log('⏭️ Sheet "' + sheetName + '" tidak ditrack');
      return;
    }

    // Skip sheet sistem
    if (sheetName.startsWith('_') || sheetName === 'Template' || sheetName === 'Config') {
      Logger.log('⏭️ Skip system sheet: ' + sheetName);
      return;
    }

    // Get user email with fallback methods
    var user = getUserEmail();

    // Jika masih kosong, gunakan fallback identifier
    if (!user || user === '') {
      Logger.log('⚠️ Cannot get user email - using fallback');
      user = 'unknown-editor';
    }

    var timestamp = new Date();
    var actionType = e.changeType === 'INSERT_ROW' ? 'INSERT_ROW' : 'DELETE_ROW';

    // Untuk INSERT_ROW, ambil data dari row yang baru ditambahkan (jika ada)
    var clientName = '';
    var kitNumber = '';
    var rowNumber = 0;
    var rowInfo = '';

    if (e.changeType === 'INSERT_ROW') {
      // Google Apps Script tidak memberikan row number yang exact untuk INSERT_ROW
      // Kita hanya bisa track bahwa ada row baru ditambahkan
      rowInfo = 'Row baru ditambahkan';
    } else if (e.changeType === 'REMOVE_ROW') {
      rowInfo = 'Row dihapus';
    }

    // Buat objek data tracking untuk row change
    var trackingData = {
      spreadsheet_id: SpreadsheetApp.getActiveSpreadsheet().getId(),
      spreadsheet_name: SpreadsheetApp.getActiveSpreadsheet().getName(),
      sheet_name: sheetName,
      user_email: user,
      timestamp: Utilities.formatDate(timestamp, 'Asia/Jakarta', 'yyyy-MM-dd HH:mm:ss'),
      row_number: 0, // Tidak spesifik untuk row changes
      column_number: 0,
      column_name: '',
      old_value: '',
      new_value: rowInfo,
      client_name: '',
      kit_number: '',
      action_type: actionType
    };

    Logger.log('📊 Tracking row change:');
    Logger.log('  Sheet: ' + sheetName);
    Logger.log('  User: ' + user);
    Logger.log('  Type: ' + actionType);
    Logger.log('  Info: ' + rowInfo);

    // Kirim ke API
    sendToTrackingAPI(trackingData);

  } catch (error) {
    Logger.log('❌ Error in onChangeTracking: ' + error.message);
    Logger.log('Stack: ' + error.stack);
  }
}

// ========================================
// 🔧 HELPER: Format Value
// ========================================

/**
 * Format generic value (untuk client name, kit number, dll)
 */
function formatValue(value) {
  if (value === null || value === undefined || value === '') {
    return '';
  }

  // Jika Date, format ke string
  if (value instanceof Date) {
    return Utilities.formatDate(value, 'Asia/Jakarta', 'dd/MM/yyyy');
  }

  return value.toString();
}

// ========================================
// 🌐 FUNGSI KIRIM DATA KE API
// ========================================

function sendToTrackingAPI(data) {
  try {
    var options = {
      'method': 'post',
      'contentType': 'application/json',
      'payload': JSON.stringify(data),
      'muteHttpExceptions': true
    };

    Logger.log('📤 Sending to API: ' + TRACKING_API_URL);

    var response = UrlFetchApp.fetch(TRACKING_API_URL, options);
    var responseCode = response.getResponseCode();
    var responseText = response.getContentText();

    if (responseCode === 200) {
      Logger.log('✅ Tracking data sent successfully');
      Logger.log('Response: ' + responseText);
    } else {
      Logger.log('⚠️ API returned non-200 status: ' + responseCode);
      Logger.log('Response: ' + responseText);
    }

  } catch (error) {
    Logger.log('❌ Error sending to API: ' + error.message);
    // Simpan ke fallback jika API gagal
    saveFallbackLog(data, error.message);
  }
}

// ========================================
// 💾 FALLBACK: SIMPAN KE SHEET LOG
// ========================================

function saveFallbackLog(data, errorMessage) {
  try {
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var logSheet = ss.getSheetByName('_Tracking_Log');

    // Buat sheet log jika belum ada
    if (!logSheet) {
      logSheet = ss.insertSheet('_Tracking_Log');
      logSheet.appendRow([
        'Timestamp', 'Sheet', 'User', 'Cell', 'Old Value', 'New Value',
        'Client Name', 'KIT Number', 'API Error'
      ]);
      logSheet.getRange(1, 1, 1, 9).setFontWeight('bold').setBackground('#4285f4').setFontColor('#ffffff');
    }

    // Tambah log
    logSheet.appendRow([
      data.timestamp,
      data.sheet_name,
      data.user_email,
      data.column_name + data.row_number,
      data.old_value,
      data.new_value,
      data.client_name,
      data.kit_number,
      errorMessage || 'Failed to send to API'
    ]);

    Logger.log('💾 Saved to fallback log sheet');

  } catch (logError) {
    Logger.log('❌ Failed to save fallback log: ' + logError.message);
  }
}

// ========================================
// 🔧 HELPER FUNCTIONS
// ========================================

/**
 * Get user email with multiple fallback methods
 */
function getUserEmail() {
  try {
    // Method 1: Session.getActiveUser() - Most reliable for installable triggers
    var email = Session.getActiveUser().getEmail();
    if (email && email !== '') {
      return email;
    }

    // Method 2: Session.getEffectiveUser() - Alternative method
    email = Session.getEffectiveUser().getEmail();
    if (email && email !== '') {
      return email;
    }

    // Method 3: Get owner email as last resort
    email = SpreadsheetApp.getActiveSpreadsheet().getOwner().getEmail();
    if (email && email !== '') {
      Logger.log('⚠️ Using owner email as fallback: ' + email);
      return email + ' (owner-fallback)';
    }

  } catch (error) {
    Logger.log('❌ Error getting user email: ' + error.message);
  }

  return ''; // Return empty if all methods fail
}

/**
 * Convert column number to letter (1 = A, 2 = B, dst)
 */
function getColumnLetter(columnNumber) {
  var letter = '';
  while (columnNumber > 0) {
    var temp = (columnNumber - 1) % 26;
    letter = String.fromCharCode(temp + 65) + letter;
    columnNumber = (columnNumber - temp - 1) / 26;
  }
  return letter;
}

/**
 * Fungsi untuk testing manual (tanpa perlu edit sheet)
 */
function testTracking() {
  var testData = {
    spreadsheet_id: 'test-spreadsheet-id',
    spreadsheet_name: 'Test Spreadsheet',
    sheet_name: 'Client Aktif',
    user_email: 'test@example.com',
    timestamp: Utilities.formatDate(new Date(), 'Asia/Jakarta', 'yyyy-MM-dd HH:mm:ss'),
    row_number: 5,
    column_number: 11,
    column_name: 'K',
    old_value: '15/12/2024',
    new_value: '20/12/2024',
    client_name: 'Test Client',
    kit_number: 'KIT-123456'
  };

  Logger.log('🧪 Testing tracking system...');
  sendToTrackingAPI(testData);
}

/**
 * Test konversi serial number ke tanggal
 */
function testSerialToDate() {
  // Test dengan serial number dari user: 46056 dan 46025
  var serial1 = 46056;
  var serial2 = 46025;

  Logger.log('Testing serial to date conversion:');

  // Konversi serial 1
  var date1 = new Date(Math.round((serial1 - 25569) * 86400 * 1000));
  var formatted1 = Utilities.formatDate(date1, 'Asia/Jakarta', 'dd/MM/yyyy');
  Logger.log('Serial ' + serial1 + ' = ' + formatted1 + ' (Year: ' + date1.getFullYear() + ')');

  // Konversi serial 2
  var date2 = new Date(Math.round((serial2 - 25569) * 86400 * 1000));
  var formatted2 = Utilities.formatDate(date2, 'Asia/Jakarta', 'dd/MM/yyyy');
  Logger.log('Serial ' + serial2 + ' = ' + formatted2 + ' (Year: ' + date2.getFullYear() + ')');
}

/**
 * Fungsi untuk retry mengirim log yang gagal
 */
function retryFailedLogs() {
  try {
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var logSheet = ss.getSheetByName('_Tracking_Log');

    if (!logSheet) {
      Logger.log('⚠️ No fallback log sheet found');
      return;
    }

    var data = logSheet.getDataRange().getValues();
    var retriedCount = 0;

    for (var i = 1; i < data.length; i++) { // Skip header
      var row = data[i];

      var trackingData = {
        spreadsheet_id: SpreadsheetApp.getActiveSpreadsheet().getId(),
        spreadsheet_name: SpreadsheetApp.getActiveSpreadsheet().getName(),
        sheet_name: row[1],
        user_email: row[2],
        timestamp: Utilities.formatDate(new Date(row[0]), 'Asia/Jakarta', 'yyyy-MM-dd HH:mm:ss'),
        row_number: parseInt(row[3].replace(/[^0-9]/g, '')),
        column_number: getColumnNumberFromLetter(row[3].replace(/[0-9]/g, '')),
        column_name: row[3].replace(/[0-9]/g, ''),
        old_value: row[4],
        new_value: row[5],
        client_name: row[6],
        kit_number: row[7]
      };

      sendToTrackingAPI(trackingData);
      retriedCount++;

      // Delete row setelah berhasil retry
      logSheet.deleteRow(i + 1);
    }

    Logger.log('✅ Retried ' + retriedCount + ' failed logs');

  } catch (error) {
    Logger.log('❌ Error retrying failed logs: ' + error.message);
  }
}

function getColumnNumberFromLetter(letter) {
  var column = 0;
  for (var i = 0; i < letter.length; i++) {
    column = column * 26 + (letter.charCodeAt(i) - 64);
  }
  return column;
}

// ========================================
// 📚 INSTALASI & DOKUMENTASI
// ========================================

/**
 * CARA INSTALASI:
 *
 * 1. Copy semua kode ini ke Apps Script Editor
 * 2. TRACKING_API_URL SUDAH DIGANTI ke: https://star.octolink.id/api/track-change.php
 * 3. HAPUS TRIGGER LAMA (jika ada):
 *    - Klik icon jam (⏰) di sidebar
 *    - Hapus semua trigger yang ada
 * 4. Install TRIGGER BARU (INSTALL 2 TRIGGER!):
 *
 *    TRIGGER 1 - Track UPDATE/INSERT/DELETE (cell edit):
 *    - Klik "+ Add Trigger"
 *    - Function: onEditTracking
 *    - Event source: From spreadsheet
 *    - Event type: On edit
 *    - Klik Save
 *
 *    TRIGGER 2 - Track INSERT_ROW/DELETE_ROW:
 *    - Klik "+ Add Trigger" lagi
 *    - Function: onChangeTracking
 *    - Event source: From spreadsheet
 *    - Event type: On change
 *    - Klik Save
 *
 * 5. Authorize script saat diminta - PENTING: ALLOW SEMUA PERMISSIONS!
 * 6. Test dengan edit cell
 *
 * TESTING:
 * - Test tracking: Run function testTracking()
 * - Test getUserEmail(): Lihat log saat edit cell
 *
 * TROUBLESHOOTING:
 * - Cek log: View > Executions
 * - Jika User masih kosong: Re-authorize trigger
 * - Lihat fallback log: Sheet "_Tracking_Log" jika API gagal
 * - Pastikan kedua trigger sudah terpasang!
 *
 * CATATAN PENTING:
 * - getUserEmail() punya 3 fallback methods
 * - Jika semua gagal, tracking akan di-skip dengan log error
 * - Re-authorize trigger jika masih return empty user email
 */
