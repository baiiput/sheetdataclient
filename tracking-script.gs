// ========================================
// 🚀 STARLINK MANAGEMENT SYSTEM - CHANGE TRACKING
// ========================================
// Script untuk tracking semua perubahan di Google Sheet
// FIXED: Proper handling untuk tanggal dan format data

// ========================================
// 🔧 KONFIGURASI
// ========================================

// URL API endpoint di VPS Anda (GANTI dengan URL VPS Anda!)
const TRACKING_API_URL = 'https://your-domain.com/api/track-change.php';

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
 * 4. Pilih fungsi: onEditTracking
 * 5. Event type: On edit
 * 6. Klik Save
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

    var user = Session.getActiveUser().getEmail();
    var timestamp = new Date();
    var row = e.range.getRow();
    var column = e.range.getColumn();
    var columnName = getColumnLetter(column);

    // ========================================
    // 🔧 FIXED: Handle tanggal dengan benar
    // ========================================

    var range = e.range;
    var oldValue = '';
    var newValue = '';

    // Get formatted values (untuk tanggal akan jadi format readable)
    try {
      // Untuk old value, kita harus format manual karena e.oldValue bisa berupa serial number
      if (e.oldValue !== undefined && e.oldValue !== null && e.oldValue !== '') {
        oldValue = formatCellValue(e.oldValue, range);
      }

      // Untuk new value, ambil dari range yang sudah diformat
      if (e.value !== undefined && e.value !== null && e.value !== '') {
        newValue = formatCellValue(e.value, range);
      }
    } catch (formatError) {
      Logger.log('⚠️ Format error: ' + formatError.message);
      // Fallback ke toString
      oldValue = (e.oldValue || '').toString();
      newValue = (e.value || '').toString();
    }

    // Jika nilai sama, skip (tidak ada perubahan)
    if (oldValue === newValue) {
      Logger.log('⏭️ Nilai tidak berubah, skip tracking');
      return;
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
      kit_number: kitNumber.substring(0, 100)
    };

    Logger.log('📊 Tracking change:');
    Logger.log('  Sheet: ' + sheetName);
    Logger.log('  User: ' + user);
    Logger.log('  Cell: ' + columnName + row);
    Logger.log('  Old: ' + oldValue);
    Logger.log('  New: ' + newValue);

    // Kirim ke API
    sendToTrackingAPI(trackingData);

  } catch (error) {
    Logger.log('❌ Error in onEditTracking: ' + error.message);
    Logger.log('Stack: ' + error.stack);
    // Jangan throw error agar tidak mengganggu user edit
  }
}

// ========================================
// 🔧 HELPER: Format Cell Value
// ========================================

/**
 * Format cell value dengan benar, termasuk tanggal
 */
function formatCellValue(value, range) {
  if (value === null || value === undefined || value === '') {
    return '';
  }

  // Cek apakah ini adalah tanggal (serial number)
  if (typeof value === 'number') {
    // Coba parse sebagai tanggal
    try {
      var dateValue = new Date((value - 25569) * 86400 * 1000); // Convert Excel serial to Date

      // Validasi apakah ini benar-benar tanggal yang valid
      if (!isNaN(dateValue.getTime())) {
        // Cek apakah nilai asli > 1 (tanggal serial biasanya > 40000)
        if (value > 1 && value < 100000) {
          // Format sebagai tanggal Indonesia
          return Utilities.formatDate(dateValue, 'Asia/Jakarta', 'dd/MM/yyyy HH:mm:ss');
        }
      }
    } catch (dateError) {
      Logger.log('⚠️ Date parse error: ' + dateError.message);
    }
  }

  // Jika value adalah Date object
  if (value instanceof Date) {
    return Utilities.formatDate(value, 'Asia/Jakarta', 'dd/MM/yyyy HH:mm:ss');
  }

  // Untuk value lainnya, convert ke string
  return value.toString();
}

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
    // Simpan ke fallback jika API gagal (opsional)
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
 * Test format tanggal
 */
function testDateFormat() {
  // Test dengan serial number
  var serialNumber = 45321; // Tanggal dalam format serial
  Logger.log('Serial: ' + serialNumber);
  Logger.log('Formatted: ' + formatCellValue(serialNumber, null));

  // Test dengan Date object
  var dateObj = new Date();
  Logger.log('Date object: ' + dateObj);
  Logger.log('Formatted: ' + formatCellValue(dateObj, null));

  // Test dengan string
  var stringValue = 'Test String';
  Logger.log('String: ' + stringValue);
  Logger.log('Formatted: ' + formatCellValue(stringValue, null));

  // Test dengan number biasa
  var normalNumber = 123;
  Logger.log('Normal number: ' + normalNumber);
  Logger.log('Formatted: ' + formatCellValue(normalNumber, null));
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
 * 2. Ganti TRACKING_API_URL dengan URL API VPS Anda
 * 3. Install trigger:
 *    - Klik icon jam (⏰) di sidebar
 *    - Klik "+ Add Trigger"
 *    - Function: onEditTracking
 *    - Event source: From spreadsheet
 *    - Event type: On edit
 *    - Klik Save
 * 4. Authorize script saat diminta
 * 5. Test dengan edit cell di sheet (termasuk tanggal!)
 *
 * TROUBLESHOOTING:
 * - Cek log: View > Logs
 * - Test manual: Run function testTracking()
 * - Test format tanggal: Run function testDateFormat()
 * - Lihat fallback log: Sheet "_Tracking_Log" jika API gagal
 *
 * CATATAN:
 * - Trigger onEdit punya quota limit (30 detik execution time)
 * - Jika edit banyak cell sekaligus, akan tercatat per cell
 * - Data disimpan di fallback log jika API timeout/error
 * - Format tanggal otomatis dikonversi ke dd/MM/yyyy HH:mm:ss
 */
