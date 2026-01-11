// ========================================
// 🚀 STARLINK MANAGEMENT SYSTEM - CHANGE TRACKING
// ========================================
// Script untuk tracking semua perubahan di Google Sheet
// SIMPLE VERSION - Track semua sheet dengan Nomor WA Client

// ========================================
// 🔧 KONFIGURASI
// ========================================

// URL API endpoint di VPS Anda
const TRACKING_API_URL = 'https://star.octolink.id/api/track-change.php';

// TRACK SEMUA SHEET (kosongkan array atau hapus filter)
const TRACKED_SHEETS = []; // Kosong = track semua sheet

// Konfigurasi kolom data
const COLUMN_CLIENT_NAME = 0;  // Kolom A (index 0) = Nama Client
const COLUMN_WA_NUMBER = 6;    // Kolom G (index 6) = Nomor WA
const COLUMN_KIT_NUMBER = 8;   // Kolom I (index 8) = KIT Number

// ========================================
// 📝 FUNGSI UTAMA - ON EDIT TRIGGER
// ========================================

function onEditTracking(e) {
  try {
    // Validasi event
    if (!e || !e.range) {
      Logger.log('⚠️ Event tidak valid atau tidak ada range yang diubah');
      return;
    }

    var sheet = e.range.getSheet();
    var sheetName = sheet.getName();

    // Skip jika sheet tidak ingin ditrack (hanya jika TRACKED_SHEETS tidak kosong)
    if (TRACKED_SHEETS.length > 0 && TRACKED_SHEETS.indexOf(sheetName) === -1) {
      Logger.log('⏭️ Sheet "' + sheetName + '" tidak ditrack');
      return;
    }

    // Skip sheet sistem atau template
    if (sheetName.startsWith('_') || sheetName === 'Template' || sheetName === 'Config') {
      Logger.log('⏭️ Skip system sheet: ' + sheetName);
      return;
    }

    var timestamp = new Date();
    var row = e.range.getRow();
    var column = e.range.getColumn();
    var columnName = getColumnLetter(column);

    // ========================================
    // 📱 AMBIL NOMOR WA CLIENT DARI ROW DATA
    // ========================================

    var waNumber = '';
    try {
      var rowData = sheet.getRange(row, 1, 1, sheet.getLastColumn()).getValues()[0];
      var rawWA = formatValue(rowData[COLUMN_WA_NUMBER]);

      // Clean nomor WA (hapus spasi, tanda hubung, dll)
      if (rawWA && rawWA !== '') {
        waNumber = rawWA.toString().replace(/\D/g, ''); // Hapus semua non-digit

        // Tambahkan 62 jika diawali 0 (format Indonesia)
        if (waNumber.startsWith('0')) {
          waNumber = '62' + waNumber.substring(1);
        }

        // Pastikan diawali 62
        if (!waNumber.startsWith('62')) {
          waNumber = '62' + waNumber;
        }
      }

      Logger.log('📱 Nomor WA Client: ' + waNumber);
    } catch (err) {
      Logger.log('⚠️ Error getting WA number: ' + err.message);
      waNumber = '';
    }

    // ========================================
    // 🔧 GET OLD & NEW VALUES
    // ========================================

    var oldValue = '';
    var newValue = '';

    // NEW VALUE: Gunakan getDisplayValue()
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

    // OLD VALUE: Format manual
    if (e.oldValue !== undefined && e.oldValue !== null && e.oldValue !== '') {
      var numValue = typeof e.oldValue === 'number' ? e.oldValue : parseFloat(e.oldValue);

      // Cek apakah ini kemungkinan tanggal (serial number)
      if (!isNaN(numValue) && numValue > 1000 && numValue < 100000) {
        try {
          var serialDate = new Date(Math.round((Math.floor(numValue) - 25569) * 86400 * 1000));
          if (serialDate.getFullYear() >= 1900 && serialDate.getFullYear() <= 2100) {
            oldValue = Utilities.formatDate(serialDate, 'Asia/Jakarta', 'dd/MM/yyyy');
          } else {
            oldValue = e.oldValue.toString();
          }
        } catch (dateErr) {
          oldValue = e.oldValue.toString();
        }
      } else {
        oldValue = e.oldValue.toString();
      }
    }

    // Jika nilai sama, skip
    if (oldValue === newValue) {
      Logger.log('⏭️ Nilai tidak berubah, skip tracking');
      return;
    }

    // Deteksi action type
    var actionType = 'UPDATE';
    if ((oldValue === '' || oldValue === null) && newValue !== '') {
      actionType = 'INSERT';
    } else if ((newValue === '' || newValue === null) && oldValue !== '') {
      actionType = 'DELETE';
    }

    // Ambil informasi tambahan dari row
    var rowData = sheet.getRange(row, 1, 1, sheet.getLastColumn()).getValues()[0];
    var clientName = formatValue(rowData[COLUMN_CLIENT_NAME]);
    var kitNumber = formatValue(rowData[COLUMN_KIT_NUMBER]);

    // Buat objek data tracking
    var trackingData = {
      spreadsheet_id: SpreadsheetApp.getActiveSpreadsheet().getId(),
      spreadsheet_name: SpreadsheetApp.getActiveSpreadsheet().getName(),
      sheet_name: sheetName,
      user_email: waNumber, // Kirim nomor WA di field user_email
      timestamp: Utilities.formatDate(timestamp, 'Asia/Jakarta', 'yyyy-MM-dd HH:mm:ss'),
      row_number: row,
      column_number: column,
      column_name: columnName,
      old_value: oldValue.substring(0, 1000),
      new_value: newValue.substring(0, 1000),
      client_name: clientName.substring(0, 255),
      kit_number: kitNumber.substring(0, 100),
      action_type: actionType
    };

    Logger.log('📊 Tracking change:');
    Logger.log('  Sheet: ' + sheetName);
    Logger.log('  WA Number: ' + waNumber);
    Logger.log('  Client: ' + clientName);
    Logger.log('  Cell: ' + columnName + row);
    Logger.log('  Old: ' + oldValue);
    Logger.log('  New: ' + newValue);
    Logger.log('  Type: ' + actionType);

    // Kirim ke API
    sendToTrackingAPI(trackingData);

  } catch (error) {
    Logger.log('❌ Error in onEditTracking: ' + error.message);
    Logger.log('Stack: ' + error.stack);
  }
}

// ========================================
// 📝 FUNGSI ON CHANGE - TRACK INSERT/DELETE ROW
// ========================================

function onChangeTracking(e) {
  try {
    if (!e) {
      Logger.log('⚠️ Event tidak valid');
      return;
    }

    Logger.log('📋 onChange Event detected:');
    Logger.log('  Type: ' + e.changeType);

    if (e.changeType !== 'INSERT_ROW' && e.changeType !== 'REMOVE_ROW') {
      Logger.log('⏭️ Skip - bukan insert/delete row');
      return;
    }

    var sheet = SpreadsheetApp.getActiveSheet();
    var sheetName = sheet.getName();

    if (TRACKED_SHEETS.length > 0 && TRACKED_SHEETS.indexOf(sheetName) === -1) {
      Logger.log('⏭️ Sheet "' + sheetName + '" tidak ditrack');
      return;
    }

    if (sheetName.startsWith('_') || sheetName === 'Template' || sheetName === 'Config') {
      Logger.log('⏭️ Skip system sheet: ' + sheetName);
      return;
    }

    var timestamp = new Date();
    var actionType = e.changeType === 'INSERT_ROW' ? 'INSERT_ROW' : 'DELETE_ROW';
    var rowInfo = e.changeType === 'INSERT_ROW' ? 'Row baru ditambahkan' : 'Row dihapus';

    var trackingData = {
      spreadsheet_id: SpreadsheetApp.getActiveSpreadsheet().getId(),
      spreadsheet_name: SpreadsheetApp.getActiveSpreadsheet().getName(),
      sheet_name: sheetName,
      user_email: '', // Tidak ada nomor WA untuk row changes
      timestamp: Utilities.formatDate(timestamp, 'Asia/Jakarta', 'yyyy-MM-dd HH:mm:ss'),
      row_number: 0,
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
    Logger.log('  Type: ' + actionType);
    Logger.log('  Info: ' + rowInfo);

    sendToTrackingAPI(trackingData);

  } catch (error) {
    Logger.log('❌ Error in onChangeTracking: ' + error.message);
    Logger.log('Stack: ' + error.stack);
  }
}

// ========================================
// 🔧 HELPER FUNCTIONS
// ========================================

function formatValue(value) {
  if (value === null || value === undefined || value === '') {
    return '';
  }

  if (value instanceof Date) {
    return Utilities.formatDate(value, 'Asia/Jakarta', 'dd/MM/yyyy');
  }

  return value.toString();
}

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
    saveFallbackLog(data, error.message);
  }
}

function saveFallbackLog(data, errorMessage) {
  try {
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    var logSheet = ss.getSheetByName('_Tracking_Log');

    if (!logSheet) {
      logSheet = ss.insertSheet('_Tracking_Log');
      logSheet.appendRow([
        'Timestamp', 'Sheet', 'WA Number', 'Cell', 'Old Value', 'New Value',
        'Client Name', 'KIT Number', 'API Error'
      ]);
      logSheet.getRange(1, 1, 1, 9).setFontWeight('bold').setBackground('#4285f4').setFontColor('#ffffff');
    }

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

function getColumnLetter(columnNumber) {
  var letter = '';
  while (columnNumber > 0) {
    var temp = (columnNumber - 1) % 26;
    letter = String.fromCharCode(temp + 65) + letter;
    columnNumber = (columnNumber - temp - 1) / 26;
  }
  return letter;
}

function testTracking() {
  var testData = {
    spreadsheet_id: 'test-id',
    spreadsheet_name: 'Test Spreadsheet',
    sheet_name: 'Client Aktif',
    user_email: '628123456789',
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

// ========================================
// 📚 INSTALASI
// ========================================

/**
 * CARA INSTALASI:
 *
 * 1. Copy semua kode ini ke Apps Script Editor (Code.gs)
 * 2. SESUAIKAN KOLOM:
 *    - COLUMN_WA_NUMBER = index kolom yang berisi Nomor WA (0=A, 1=B, 2=C, dst)
 * 3. Install 2 trigger:
 *    - onEditTracking (On edit)
 *    - onChangeTracking (On change)
 * 4. Save & test!
 *
 * CATATAN:
 * - Script akan track SEMUA sheet (kecuali yang diawali _ atau bernama Template/Config)
 * - Nomor WA otomatis diformat ke format internasional (62xxx)
 * - Nomor WA disimpan di field user_email
 */
