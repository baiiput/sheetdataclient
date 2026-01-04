<?php
// ========================================
// 🔍 DEBUG RENDER - CEK KENAPA DATA TIDAK TAMPIL
// ========================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Render</title>
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
            font-size: 13px;
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
        pre {
            background: #1a202c;
            padding: 16px;
            border-radius: 6px;
            overflow-x: auto;
            border: 1px solid #4a5568;
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
        .null-value { color: #a0aec0; font-style: italic; }
    </style>
</head>
<body>
<div class="container">

<h1>🔍 Debug Render - Kenapa Data Tidak Tampil</h1>

<?php
try {
    $db = Database::getInstance();

    echo "<h2>1. Query dari getChangeLogs()</h2>";

    $result = getChangeLogs([], 1, 50);
    $logs = $result['logs'];
    $pagination = $result['pagination'];

    echo "<p>Total records: <strong>" . $pagination['total_records'] . "</strong></p>";
    echo "<p>Logs array count: <strong>" . count($logs) . "</strong></p>";

    if (count($logs) > 0) {
        echo "<h2>2. Data Mentah (Array Keys)</h2>";
        echo "<p>Keys dari record pertama:</p>";
        echo "<pre>";
        print_r(array_keys($logs[0]));
        echo "</pre>";

        echo "<h2>3. Simulasi Render seperti di index.php</h2>";

        echo "<table>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Waktu</th>";
        echo "<th>User</th>";
        echo "<th>Sheet</th>";
        echo "<th>Cell</th>";
        echo "<th>Old Value</th>";
        echo "<th>New Value</th>";
        echo "<th>Client</th>";
        echo "<th>KIT</th>";
        echo "<th>Tipe</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        foreach ($logs as $log) {
            echo "<tr>";

            // ID
            echo "<td>";
            if (isset($log['id'])) {
                echo htmlspecialchars($log['id']);
            } else {
                echo "<span class='null-value'>NULL (key 'id' not found)</span>";
            }
            echo "</td>";

            // Waktu
            echo "<td>";
            if (isset($log['changed_at'])) {
                try {
                    echo formatDate($log['changed_at'], 'd/m/Y') . "<br>";
                    echo "<small>" . formatDate($log['changed_at'], 'H:i:s') . "</small>";
                } catch (Exception $e) {
                    echo "<span class='badge badge-error'>Error: " . $e->getMessage() . "</span>";
                }
            } else {
                echo "<span class='null-value'>NULL (key 'changed_at' not found)</span>";
            }
            echo "</td>";

            // User
            echo "<td>";
            if (isset($log['user_email'])) {
                echo htmlspecialchars(truncate($log['user_email'], 25));
            } else {
                echo "<span class='null-value'>NULL (key 'user_email' not found)</span>";
            }
            echo "</td>";

            // Sheet
            echo "<td>";
            if (isset($log['sheet_name'])) {
                echo "<span class='badge badge-success'>" . htmlspecialchars($log['sheet_name']) . "</span>";
            } else {
                echo "<span class='null-value'>NULL (key 'sheet_name' not found)</span>";
            }
            echo "</td>";

            // Cell
            echo "<td>";
            if (isset($log['cell_address'])) {
                echo "<code>" . htmlspecialchars($log['cell_address']) . "</code>";
            } else {
                echo "<span class='null-value'>NULL (key 'cell_address' not found)</span>";
            }
            echo "</td>";

            // Old Value
            echo "<td>";
            if (isset($log['old_value'])) {
                $oldVal = $log['old_value'] ?? '-';
                echo htmlspecialchars(truncate($oldVal, 30));
            } else {
                echo "<span class='null-value'>NULL (key 'old_value' not found)</span>";
            }
            echo "</td>";

            // New Value
            echo "<td>";
            if (isset($log['new_value'])) {
                $newVal = $log['new_value'] ?? '-';
                echo htmlspecialchars(truncate($newVal, 30));
            } else {
                echo "<span class='null-value'>NULL (key 'new_value' not found)</span>";
            }
            echo "</td>";

            // Client
            echo "<td>";
            if (isset($log['client_name'])) {
                $clientName = $log['client_name'] ?? '-';
                echo htmlspecialchars(truncate($clientName, 20));
            } else {
                echo "<span class='null-value'>NULL (key 'client_name' not found)</span>";
            }
            echo "</td>";

            // KIT
            echo "<td>";
            if (isset($log['kit_number'])) {
                echo htmlspecialchars($log['kit_number'] ?? '-');
            } else {
                echo "<span class='null-value'>NULL (key 'kit_number' not found)</span>";
            }
            echo "</td>";

            // Tipe
            echo "<td>";
            if (isset($log['old_value']) && isset($log['new_value'])) {
                try {
                    $badge = getChangeTypeBadge($log['old_value'], $log['new_value']);
                    $label = getChangeTypeLabel($log['old_value'], $log['new_value']);
                    echo "<span class='badge {$badge}'>{$label}</span>";
                } catch (Exception $e) {
                    echo "<span class='badge badge-error'>Error: " . $e->getMessage() . "</span>";
                }
            } else {
                echo "<span class='null-value'>Cannot determine (keys not found)</span>";
            }
            echo "</td>";

            echo "</tr>";
        }

        echo "</tbody>";
        echo "</table>";

        echo "<h2>4. Detail Record Pertama (Full)</h2>";
        echo "<pre>";
        print_r($logs[0]);
        echo "</pre>";

        echo "<h2>5. Diagnosa</h2>";

        $issues = [];

        // Check for missing keys
        $requiredKeys = ['id', 'changed_at', 'user_email', 'sheet_name', 'cell_address', 'old_value', 'new_value', 'client_name', 'kit_number'];
        $missingKeys = [];

        foreach ($requiredKeys as $key) {
            if (!isset($logs[0][$key])) {
                $missingKeys[] = $key;
            }
        }

        if (!empty($missingKeys)) {
            $issues[] = "<span class='badge badge-error'>❌</span> Missing keys: " . implode(', ', $missingKeys);
        }

        // Check for NULL values
        $nullKeys = [];
        foreach ($logs[0] as $key => $value) {
            if ($value === null) {
                $nullKeys[] = $key;
            }
        }

        if (!empty($nullKeys)) {
            $issues[] = "<span class='badge badge-error'>⚠️</span> NULL values in: " . implode(', ', $nullKeys);
        }

        if (empty($issues)) {
            echo "<p><span class='badge badge-success'>✅ Semua keys ada dan tidak NULL!</span></p>";
            echo "<p>Kalau data tidak tampil di index.php, kemungkinan masalah di:</p>";
            echo "<ul>";
            echo "<li>Session/login issue</li>";
            echo "<li>CSS hiding the data</li>";
            echo "<li>JavaScript error</li>";
            echo "</ul>";
        } else {
            echo "<ul>";
            foreach ($issues as $issue) {
                echo "<li>{$issue}</li>";
            }
            echo "</ul>";
        }

    } else {
        echo "<p><span class='badge badge-error'>❌ Array logs KOSONG!</span></p>";
        echo "<p>Tapi total_records = " . $pagination['total_records'] . "</p>";
        echo "<p>Ini berarti ada masalah di query atau pagination offset.</p>";
    }

} catch (Exception $e) {
    echo "<div style='background: #f56565; color: white; padding: 16px; border-radius: 8px;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
?>

</div>
</body>
</html>
