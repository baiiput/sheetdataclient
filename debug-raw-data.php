<?php
// ========================================
// 🔍 DEBUG RAW DATA - CEK ISI DATABASE
// ========================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/db.php';

echo "<pre style='background: #1a202c; color: #f7fafc; padding: 20px; border-radius: 8px; font-size: 12px;'>";
echo "🔍 DEBUG RAW DATA - CEK ISI DATABASE\n";
echo "=====================================\n\n";

try {
    $db = Database::getInstance();
    echo "✅ Database connected\n\n";

    echo "--- DIRECT SQL QUERY (NO FUNCTIONS) ---\n\n";

    $sql = "SELECT * FROM change_logs ORDER BY id DESC LIMIT 3";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Total records: " . count($logs) . "\n\n";

    foreach ($logs as $index => $log) {
        echo "========================================\n";
        echo "RECORD #" . ($index + 1) . " (ID: " . $log['id'] . ")\n";
        echo "========================================\n\n";

        foreach ($log as $column => $value) {
            $valueType = gettype($value);
            $valueDisplay = $value === null ? 'NULL' :
                           ($value === '' ? '(empty string)' :
                           (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value));

            $status = ($value === null || $value === '') ? '❌' : '✅';

            printf("%-20s %s %-10s = %s\n", $column, $status, "($valueType)", $valueDisplay);
        }
        echo "\n";
    }

    echo "========================================\n";
    echo "SUMMARY\n";
    echo "========================================\n\n";

    if (!empty($logs)) {
        $firstLog = $logs[0];

        echo "Checking required fields for display:\n\n";

        $requiredFields = [
            'id' => 'ID Record',
            'user_email' => 'Email User',
            'changed_at' => 'Waktu Perubahan',
            'sheet_name' => 'Nama Sheet',
            'cell_address' => 'Alamat Cell',
            'old_value' => 'Nilai Lama',
            'new_value' => 'Nilai Baru',
            'client_name' => 'Nama Client',
            'kit_number' => 'Nomor KIT'
        ];

        $nullFields = [];
        $emptyFields = [];
        $okFields = [];

        foreach ($requiredFields as $field => $label) {
            if (!isset($firstLog[$field])) {
                echo "⚠️  $label ($field): KEY NOT FOUND IN RESULT!\n";
            } else if ($firstLog[$field] === null) {
                $nullFields[] = "$label ($field)";
            } else if ($firstLog[$field] === '') {
                $emptyFields[] = "$label ($field)";
            } else {
                $okFields[] = "$label ($field)";
            }
        }

        echo "\n";
        echo "✅ Fields dengan data (" . count($okFields) . "):\n";
        if (!empty($okFields)) {
            foreach ($okFields as $field) {
                echo "   - $field\n";
            }
        } else {
            echo "   (none)\n";
        }

        echo "\n";
        echo "❌ Fields NULL (" . count($nullFields) . "):\n";
        if (!empty($nullFields)) {
            foreach ($nullFields as $field) {
                echo "   - $field\n";
            }
        } else {
            echo "   (none)\n";
        }

        echo "\n";
        echo "⚠️  Fields EMPTY STRING (" . count($emptyFields) . "):\n";
        if (!empty($emptyFields)) {
            foreach ($emptyFields as $field) {
                echo "   - $field\n";
            }
        } else {
            echo "   (none)\n";
        }

        echo "\n\n";
        echo "DIAGNOSIS:\n";
        if (count($nullFields) > 0 || count($emptyFields) > 0) {
            echo "❌ Data di database tidak lengkap!\n\n";
            echo "KEMUNGKINAN PENYEBAB:\n";
            echo "1. Google Apps Script tidak mengirim data lengkap\n";
            echo "2. API endpoint (track-change.php) tidak menyimpan data dengan benar\n";
            echo "3. Ada error saat insert yang tidak terlihat\n\n";
            echo "SOLUSI:\n";
            echo "1. Coba edit cell di Google Sheet lagi (cell yang bukan tanggal)\n";
            echo "2. Cek Apps Script Execution log di Google\n";
            echo "3. Cek file api/track-change.php untuk debug logging\n";
        } else {
            echo "✅ Semua data lengkap!\n";
            echo "   Kalau tabel tetap kosong, masalah di rendering HTML/CSS.\n";
        }
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n✅ DEBUG COMPLETE!\n";
echo "</pre>";
?>
