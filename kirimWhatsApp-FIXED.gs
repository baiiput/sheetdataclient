/**
 * 🔄 UPDATED: Main WhatsApp function dengan kolom mapping baru
 */
function kirimWhatsApp() {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();

  // Hapus filter jika ada
  if (sheet.getFilter()) {
    sheet.getFilter().remove();
  }

  // Delay 3 detik
  Utilities.sleep(3000);

  var data = sheet.getDataRange().getValues();
  var headerRow = data[0];
  var waColumnIndex = -1;

  // ✅ UPDATED: Column indices sesuai mapping baru
  var tandaColumnIndex = COLUMNS.LABEL_JATUH_TEMPO; // U: "Jatuh Tempo"
  var statusColumnIndex = COLUMNS.STATUS; // O: Status "Lunas"
  var linkColumnIndex = COLUMNS.TOMBOL_WHATSAPP; // Y: Link WA
  var prosesColumnIndex = COLUMNS.LABEL_PROSES; // V: "PROSES"
  var segaraColumnIndex = COLUMNS.LABEL_SEGERA; // W: "SEGERA"
  var observasiColumnIndex = COLUMNS.LABEL_OBSERVASI; // X: "OBSERVASI"

  // ✅ FIX: Gunakan object untuk logs agar bisa dimodifikasi by reference
  var logs = {
    clientProsesLog: "CLIENT YANG PROSES HARI INI:\n\n",
    clientReminderH3Log: "CLIENT YANG HARUS REMINDER H-3 HARI INI:\n\n",
    clientReminderH2Log: "CLIENT YANG HARUS REMINDER H-2 HARI INI:\n\n",
    clientReminderH1Log: "CLIENT YANG HARUS REMINDER H-1 HARI INI:\n\n",
    clientJatuhTempoLog: "CLIENT YANG JATUH TEMPO DAN BELUM:\n\n",
    clientWarningLog: "CLIENT WARNING - HARI H (AKAN MATI HARI INI):\n\n",
    errorLog: ""
  };

  // Cari kolom untuk Tombol WhatsApp
  for (var c = 0; c < headerRow.length; c++) {
    if (headerRow[c].toString().trim().toLowerCase() === "tombol whatsapp") {
      waColumnIndex = c + 1;
      break;
    }
  }

  // Jika belum ada, buat kolom "Tombol WhatsApp"
  if (waColumnIndex === -1) {
    waColumnIndex = sheet.getLastColumn() + 1;
    sheet.getRange(1, waColumnIndex).setValue("Tombol WhatsApp").setFontWeight("bold").setHorizontalAlignment("center");
  }

  var today = new Date();
  today.setHours(0, 0, 0, 0);

  Logger.log("Memulai perulangan baris data...");

  // Generate link WA untuk H-3 dan HARI H
  for (var i = 1; i < data.length; i++) {
    var nama = data[i][COLUMNS.NAMA];  // A: Nama
    var email = data[i][COLUMNS.LOGIN_STARLINK]; // C: Login Starlink
    var nomorWA = data[i][COLUMNS.NOMOR_CS]; // G: Nomor WA
    var kitNumber = data[i][COLUMNS.KIT_NUMBER]; // I: KIT Number ✅ NEW
    var serialNumber = data[i][COLUMNS.SERIAL_NUMBER]; // J: Serial Number ✅ NEW
    var jatuhTempoRaw = data[i][COLUMNS.JATUH_TEMPO]; // K: Jatuh Tempo ✅ MOVED
    var paket = data[i][COLUMNS.PAKET]; // P: Paket ✅ MOVED
    var status = data[i][statusColumnIndex]; // O: Status ✅ MOVED
    var tipePelanggan = data[i][COLUMNS.TIPE_PELANGGAN]; // S: Tipe Pelanggan ✅ NEW

    // ✅ UPDATED: Cell references dengan kolom baru
    var tandaCell = sheet.getRange(i + 1, tandaColumnIndex + 1); // U: Tanda "Jatuh Tempo"
    var cell = sheet.getRange(i + 1, waColumnIndex);
    var prosesCell = sheet.getRange(i + 1, prosesColumnIndex + 1); // V: PROSES
    var segaraCell = sheet.getRange(i + 1, segaraColumnIndex + 1); // W: SEGERA
    var observasiCell = sheet.getRange(i + 1, observasiColumnIndex + 1); // X: OBSERVASI

    // Validasi hanya untuk field wajib (nama + tanggal)
    if (!nama || !jatuhTempoRaw) {
      var logWajib = `❌ Baris ke-${i + 1} dilewati karena data wajib tidak lengkap:`;
      if (!nama) logWajib += "\n   - Nama: [Kosong]";
      if (!jatuhTempoRaw) logWajib += "\n   - Jatuh Tempo: [Kosong]";

      Logger.log(logWajib);
      logs.errorLog += logWajib + "\n";

      // Clear semua status karena data wajib tidak lengkap
      tandaCell.setValue("");
      cell.setValue("").setFormula("");
      prosesCell.setValue("");
      segaraCell.setValue("");
      observasiCell.setValue("");
      continue;
    }

    // Validasi tanggal
    var jatuhTempoDate = new Date(jatuhTempoRaw);
    if (isNaN(jatuhTempoDate.getTime())) {
      var logTanggal = `❌ Baris ke-${i + 1} dilewati karena format tanggal tidak valid.`;
      Logger.log(logTanggal);
      logs.errorLog += logTanggal + "\n";

      // Clear semua status karena tanggal invalid
      tandaCell.setValue("");
      cell.setValue("").setFormula("");
      prosesCell.setValue("");
      segaraCell.setValue("");
      observasiCell.setValue("");
      continue;
    }

    // Validasi untuk field optional (tetap catat tapi tidak skip)
    var logOptional = `⚠️ Baris ke-${i + 1} data tidak lengkap (tetap diproses):`;
    var adaOptionalKosong = false;
    if (!email) { logOptional += "\n   - Email: [Kosong]"; adaOptionalKosong = true; }
    if (!nomorWA) { logOptional += "\n   - Nomor WA: [Kosong]"; adaOptionalKosong = true; }
    if (!kitNumber) { logOptional += "\n   - KIT Number: [Kosong]"; adaOptionalKosong = true; }
    if (!paket) { logOptional += "\n   - Paket: [Kosong]"; adaOptionalKosong = true; }

    if (adaOptionalKosong) {
      Logger.log(logOptional);
      logs.errorLog += logOptional + "\n";
    }

    jatuhTempoDate.setHours(0, 0, 0, 0);
    var selisihHari = Math.floor((jatuhTempoDate - today) / (1000 * 60 * 60 * 24));

    // Cek apakah status adalah "Pending"
    if (status.toString().trim().toLowerCase() === "pending") {
      observasiCell.setValue("OBSERVASI")
                   .setFontWeight("bold")
                   .setFontSize(13)
                   .setHorizontalAlignment("center")
                   .setVerticalAlignment("middle");
    } else {
      observasiCell.setValue("");
    }

    // Label "JATUH TEMPO"
    if (selisihHari === 0) {
      tandaCell.setValue("JATUH TEMPO").setFontWeight("bold").setFontColor("#1F497D").setFontSize(13).setHorizontalAlignment("center").setVerticalAlignment("middle");
    } else {
      tandaCell.setValue("");
    }

    // Status "Belum"
    if (status.toString().trim().toLowerCase() === "belum") {
      if (tandaCell.getValue() === "JATUH TEMPO") {
        logs.clientJatuhTempoLog += `${i + 1}. - ${nama} | ${email || 'N/A'} | ${nomorWA || 'N/A'} | ${kitNumber || 'N/A'}\n\n`;
      }
    }

    // Label "PROSES" jika tanggal jatuh tempo + 1 hari
    var jatuhTempoPlusOne = new Date(jatuhTempoDate);
    jatuhTempoPlusOne.setDate(jatuhTempoPlusOne.getDate() + 1);
    if (jatuhTempoPlusOne.getTime() === today.getTime()) {
      prosesCell.setValue("PROSES").setFontWeight("bold").setFontColor("#1F497D").setFontSize(13).setHorizontalAlignment("center").setVerticalAlignment("middle");
      logs.clientProsesLog += `${i + 1}. - ${nama} | ${email || 'N/A'} | ${nomorWA || 'N/A'} | ${kitNumber || 'N/A'}\n\n`;
    } else {
      prosesCell.setValue("");
    }

    // Label "SEGERA" jika status Lunas atau "Rencana Bayarkan" dan sudah lewat jatuh tempo
    if ((status.toString().trim().toLowerCase() === "lunas" || status.toString().trim().toLowerCase() === "rencana bayarkan") && jatuhTempoDate < today) {
      segaraCell.setValue("SEGERA")
                .setFontWeight("bold")
                .setFontColor("#003366")
                .setFontSize(13)
                .setHorizontalAlignment("center")
                .setVerticalAlignment("middle");
    } else {
      segaraCell.setValue("");
    }

    // Helper function untuk cek status yang memenuhi syarat
    function isEligibleStatus(status) {
      var statusLower = status.toString().trim().toLowerCase();
      return statusLower === "belum" ||
             statusLower === "active" ||
             statusLower === "pending reminder" ||
             statusLower === ""; // kosong
    }

    // ✅ FIX: Process reminder logic dengan logs object yang bisa dimodifikasi
    processReminderLogic(selisihHari, isEligibleStatus(status), {
      nama: nama,
      nomorWA: nomorWA,
      kitNumber: kitNumber,
      serialNumber: serialNumber,
      paket: paket,
      jatuhTempoDate: jatuhTempoDate,
      status: status,
      tipePelanggan: tipePelanggan,
      cell: cell,
      i: i
    }, logs); // ✅ Pass logs object by reference
  }

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

  // Reactivate filter
  reactivateFilter(sheet);
}
