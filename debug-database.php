<?php
// ========================================
// 🔍 DEBUG DATABASE - CEK DATA TRACKING
// ========================================
// File untuk debugging isi database
// HAPUS SETELAH SELESAI DEBUG!

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/db.php';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Database</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #1a202c;
            color: #f7fafc;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: #2d3748;
            padding: 32px;
            border-radius: 12px;
        }
        h1 { color: #4299e1; }
        h2 { color: #48bb78; margin-top: 32px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: #1a202c;
        }
        th, td {
            padding: 12px;
            border: 1px solid #4a5568;
            text-align: left;
        }
        th {
            background: #4a5568;
            font-weight: 600;
        }
        tr:hover {
            background: #4a5568;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-success { background: #48bb78; color: white; }
        .badge-error { background: #f56565; color: white; }
        .badge-warning { background: #ed8936; color: white; }
        pre {
            background: #1a202c;
            padding: 16px;
            border-radius: 6px;
            overflow-x: auto;
            border: 1px solid #4a5568;
        }
        .null-value {
            color: #a0aec0;
            font-style: italic;
        }
        .empty-value {
            color: #f56565;
            font-style: italic;
        }
    </style>
</head>
<body>
<div class="container">

<h1>🔍 Debug Database - Change Logs</h1>

<?php
try {
    $db = Database::getInstance();

    // ========================================
    // 1. CEK STRUKTUR TABEL
    // ========================================

    echo "<h2>1. Struktur Tabel change_logs</h2>";

    $columns = $db->fetchAll("DESCRIBE change_logs");

    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>" . ($col['Default'] ?? '<span class="null-value">NULL</span>') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // ========================================
    // 2. CEK TOTAL RECORDS
    // ========================================

    echo "<h2>2. Total Records</h2>";

    $totalRecords = $db->count('change_logs');

    if ($totalRecords > 0) {
        echo "<p><span class='badge badge-success'>✅ {$totalRecords} record(s) ditemukan</span></p>";
    } else {
        echo "<p><span class='badge badge-error'>❌ Tidak ada data di database!</span></p>";
        echo "<p>Kemungkinan:</p>";
        echo "<ul>";
        echo "<li>Database baru saja dibuat (belum ada tracking)</li>";
        echo "<li>Tabel di-truncate/dihapus</li>";
        echo "<li>Apps Script belum mengirim data</li>";
        echo "</ul>";
    }

    // ========================================
    // 3. CEK 10 RECORD TERAKHIR (RAW DATA)
    // ========================================

    if ($totalRecords > 0) {
        echo "<h2>3. Data Terakhir (10 records) - RAW</h2>";

        $logs = $db->fetchAll("SELECT * FROM change_logs ORDER BY id DESC LIMIT 10");

        echo "<table>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Spreadsheet</th>";
        echo "<th>Sheet</th>";
        echo "<th>User</th>";
        echo "<th>Changed At</th>";
        echo "<th>Row</th>";
        echo "<th>Col</th>";
        echo "<th>Cell</th>";
        echo "<th>Old Value</th>";
        echo "<th>New Value</th>";
        echo "<th>Client</th>";
        echo "<th>KIT</th>";
        echo "</tr>";

        foreach ($logs as $log) {
            echo "<tr>";
            echo "<td>{$log['id']}</td>";
            echo "<td title='" . htmlspecialchars($log['spreadsheet_name']) . "'>" . substr($log['spreadsheet_name'], 0, 20) . "...</td>";
            echo "<td>{$log['sheet_name']}</td>";
            echo "<td title='" . htmlspecialchars($log['user_email']) . "'>" . substr($log['user_email'], 0, 15) . "...</td>";
            echo "<td>{$log['changed_at']}</td>";
            echo "<td>{$log['row_num']}</td>";
            echo "<td>{$log['column_num']}</td>";
            echo "<td><code>{$log['cell_address']}</code></td>";

            // Old Value
            if ($log['old_value'] === null) {
                echo "<td><span class='null-value'>NULL</span></td>";
            } elseif ($log['old_value'] === '') {
                echo "<td><span class='empty-value'>(empty)</span></td>";
            } else {
                echo "<td title='" . htmlspecialchars($log['old_value']) . "'>" . substr($log['old_value'], 0, 30) . "...</td>";
            }

            // New Value
            if ($log['new_value'] === null) {
                echo "<td><span class='null-value'>NULL</span></td>";
            } elseif ($log['new_value'] === '') {
                echo "<td><span class='empty-value'>(empty)</span></td>";
            } else {
                echo "<td title='" . htmlspecialchars($log['new_value']) . "'>" . substr($log['new_value'], 0, 30) . "...</td>";
            }

            // Client Name
            if ($log['client_name'] === null) {
                echo "<td><span class='null-value'>NULL</span></td>";
            } elseif ($log['client_name'] === '') {
                echo "<td><span class='empty-value'>(empty)</span></td>";
            } else {
                echo "<td title='" . htmlspecialchars($log['client_name']) . "'>" . substr($log['client_name'], 0, 20) . "...</td>";
            }

            // KIT Number
            if ($log['kit_number'] === null) {
                echo "<td><span class='null-value'>NULL</span></td>";
            } elseif ($log['kit_number'] === '') {
                echo "<td><span class='empty-value'>(empty)</span></td>";
            } else {
                echo "<td>{$log['kit_number']}</td>";
            }

            echo "</tr>";
        }

        echo "</table>";

        // ========================================
        // 4. DETAIL RECORD PERTAMA
        // ========================================

        echo "<h2>4. Detail Record Terbaru (Full)</h2>";

        $firstLog = $logs[0];

        echo "<pre>";
        echo "ID: {$firstLog['id']}\n";
        echo "Spreadsheet ID: {$firstLog['spreadsheet_id']}\n";
        echo "Spreadsheet Name: {$firstLog['spreadsheet_name']}\n";
        echo "Sheet Name: {$firstLog['sheet_name']}\n";
        echo "User Email: {$firstLog['user_email']}\n";
        echo "Changed At: {$firstLog['changed_at']}\n";
        echo "Row Number: {$firstLog['row_num']}\n";
        echo "Column Number: {$firstLog['column_num']}\n";
        echo "Column Name: {$firstLog['column_name']}\n";
        echo "Cell Address: {$firstLog['cell_address']}\n";
        echo "\n--- VALUES ---\n";
        echo "Old Value: " . ($firstLog['old_value'] === null ? 'NULL' : ($firstLog['old_value'] === '' ? '(empty string)' : $firstLog['old_value'])) . "\n";
        echo "New Value: " . ($firstLog['new_value'] === null ? 'NULL' : ($firstLog['new_value'] === '' ? '(empty string)' : $firstLog['new_value'])) . "\n";
        echo "\n--- ADDITIONAL INFO ---\n";
        echo "Client Name: " . ($firstLog['client_name'] === null ? 'NULL' : ($firstLog['client_name'] === '' ? '(empty string)' : $firstLog['client_name'])) . "\n";
        echo "KIT Number: " . ($firstLog['kit_number'] === null ? 'NULL' : ($firstLog['kit_number'] === '' ? '(empty string)' : $firstLog['kit_number'])) . "\n";
        echo "\n--- METADATA ---\n";
        echo "Created At: {$firstLog['created_at']}\n";
        echo "IP Address: " . ($firstLog['ip_address'] ?? 'NULL') . "\n";
        echo "</pre>";

        // ========================================
        // 5. CEK MASALAH
        // ========================================

        echo "<h2>5. Diagnosa Masalah</h2>";

        $issues = [];

        // Cek apakah ada data dengan old_value atau new_value kosong
        $emptyValues = $db->fetchOne("
            SELECT COUNT(*) as total
            FROM change_logs
            WHERE (old_value IS NULL OR old_value = '')
               AND (new_value IS NULL OR new_value = '')
        ");

        if ($emptyValues['total'] > 0) {
            $issues[] = "<span class='badge badge-warning'>⚠️</span> {$emptyValues['total']} record memiliki old_value DAN new_value kosong/NULL";
        }

        // Cek apakah ada data dengan client_name kosong
        $emptyClient = $db->fetchOne("
            SELECT COUNT(*) as total
            FROM change_logs
            WHERE client_name IS NULL OR client_name = ''
        ");

        if ($emptyClient['total'] > 0) {
            $issues[] = "<span class='badge badge-warning'>⚠️</span> {$emptyClient['total']} record memiliki client_name kosong/NULL";
        }

        // Cek apakah ada nilai yang seperti serial number (tanggal belum diformat)
        $serialNumbers = $db->fetchAll("
            SELECT id, old_value, new_value, cell_address
            FROM change_logs
            WHERE (old_value REGEXP '^[0-9]{5}(\\.[0-9]+)?$' OR new_value REGEXP '^[0-9]{5}(\\.[0-9]+)?$')
            LIMIT 5
        ");

        if (count($serialNumbers) > 0) {
            $issues[] = "<span class='badge badge-error'>❌</span> Ditemukan " . count($serialNumbers) . " record dengan serial number (tanggal belum diformat!)";
            echo "<h3>Contoh Serial Number:</h3>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Cell</th><th>Old Value</th><th>New Value</th></tr>";
            foreach ($serialNumbers as $sn) {
                echo "<tr>";
                echo "<td>{$sn['id']}</td>";
                echo "<td><code>{$sn['cell_address']}</code></td>";
                echo "<td>" . ($sn['old_value'] ?? '<span class="null-value">NULL</span>') . "</td>";
                echo "<td>" . ($sn['new_value'] ?? '<span class="null-value">NULL</span>') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }

        if (empty($issues)) {
            echo "<p><span class='badge badge-success'>✅ Tidak ada masalah ditemukan!</span></p>";
        } else {
            echo "<ul>";
            foreach ($issues as $issue) {
                echo "<li>$issue</li>";
            }
            echo "</ul>";
        }

        // ========================================
        // 6. SOLUSI
        // ========================================

        echo "<h2>6. Solusi</h2>";

        if (count($serialNumbers) > 0) {
            echo "<div style='background: #4a5568; padding: 16px; border-radius: 8px; margin: 16px 0;'>";
            echo "<h3 style='color: #f56565;'>🚨 MASALAH DITEMUKAN: Serial Number</h3>";
            echo "<p>Ada data dengan format serial number (contoh: 45321.5) yang seharusnya tanggal.</p>";
            echo "<p><strong>Solusi:</strong></p>";
            echo "<ol>";
            echo "<li>Update Apps Script dengan kode terbaru dari repository</li>";
            echo "<li>Ganti URL API di Apps Script</li>";
            echo "<li>Save dan test dengan edit tanggal lagi</li>";
            echo "<li>Data lama dengan serial number bisa dihapus atau dibiarkan</li>";
            echo "</ol>";
            echo "<p><strong>Untuk hapus data lama:</strong></p>";
            echo "<pre>DELETE FROM change_logs WHERE id IN (" . implode(', ', array_column($serialNumbers, 'id')) . ");</pre>";
            echo "</div>";
        }

        if ($emptyValues['total'] > 0 || $emptyClient['total'] > 0) {
            echo "<div style='background: #4a5568; padding: 16px; border-radius: 8px; margin: 16px 0;'>";
            echo "<h3 style='color: #ed8936;'>⚠️ Data Kosong Ditemukan</h3>";
            echo "<p>Ada data dengan nilai kosong. Ini bisa terjadi jika:</p>";
            echo "<ul>";
            echo "<li>User menghapus isi cell (old_value ada, new_value kosong)</li>";
            echo "<li>User mengisi cell kosong (old_value kosong, new_value ada)</li>";
            echo "<li>Row yang diedit tidak punya client name atau KIT number</li>";
            echo "</ul>";
            echo "<p>Ini <strong>normal</strong> dan tidak perlu dikhawatirkan.</p>";
            echo "</div>";
        }
    }

    // ========================================
    // 7. QUICK ACTIONS
    // ========================================

    echo "<h2>7. Quick Actions</h2>";

    echo "<p><strong>Hapus SEMUA data tracking:</strong></p>";
    echo "<pre>TRUNCATE TABLE change_logs;</pre>";
    echo "<p style='color: #f56565;'>⚠️ WARNING: Ini akan menghapus SEMUA history tracking!</p>";

    echo "<p><strong>Hapus data dengan serial number saja:</strong></p>";
    echo "<pre>DELETE FROM change_logs
WHERE old_value REGEXP '^[0-9]{5}(\\.[0-9]+)?\$'
   OR new_value REGEXP '^[0-9]{5}(\\.[0-9]+)?\$';</pre>";

    echo "<p><strong>Lihat data hari ini saja:</strong></p>";
    echo "<pre>SELECT * FROM change_logs
WHERE DATE(changed_at) = CURDATE()
ORDER BY changed_at DESC;</pre>";

} catch (Exception $e) {
    echo "<div style='background: #f56565; color: white; padding: 16px; border-radius: 8px;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<hr style="margin: 40px 0; border-color: #4a5568;">

<p style="color: #a0aec0; text-align: center;">
    <strong>⚠️ HAPUS FILE INI SETELAH SELESAI DEBUG!</strong><br>
    <small>File: debug-database.php</small>
</p>

</div>
</body>
</html>
