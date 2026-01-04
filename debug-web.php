<?php
// ========================================
// 🔍 QUICK DEBUG - CEK ERROR PHP
// ========================================
// File untuk cek error PHP dan tampilkan data

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre style='background: #1a202c; color: #f7fafc; padding: 20px; border-radius: 8px;'>";
echo "🔍 DEBUG WEB DASHBOARD\n";
echo "=====================\n\n";

try {
    require_once 'includes/config.php';
    echo "✅ Config loaded\n";

    require_once 'includes/db.php';
    echo "✅ Database class loaded\n";

    require_once 'includes/functions.php';
    echo "✅ Functions loaded\n\n";

    echo "--- DATABASE CONNECTION ---\n";
    $db = Database::getInstance();
    echo "✅ Database connected\n\n";

    echo "--- QUERY TEST (Direct SQL) ---\n";
    $sql = "SELECT * FROM change_logs ORDER BY id DESC LIMIT 5";
    $logs = $db->fetchAll($sql);

    echo "Total records from direct query: " . count($logs) . "\n\n";

    if (count($logs) > 0) {
        echo "First record (raw):\n";
        print_r($logs[0]);
        echo "\n";
    }

    echo "--- FUNCTION TEST (getChangeLogs) ---\n";
    $result = getChangeLogs([], 1, 10);

    echo "Total records from getChangeLogs(): " . count($result['logs']) . "\n";
    echo "Pagination info:\n";
    print_r($result['pagination']);
    echo "\n";

    if (count($result['logs']) > 0) {
        echo "First record from function:\n";
        print_r($result['logs'][0]);
        echo "\n";
    }

    echo "--- FILTER OPTIONS TEST ---\n";
    $sheetNames = getSheetNames();
    echo "Sheet names: " . implode(', ', $sheetNames) . "\n\n";

    $userEmails = getUserEmails();
    echo "User emails: " . implode(', ', $userEmails) . "\n\n";

    echo "--- STATISTICS TEST ---\n";
    $stats = getDashboardStats();
    print_r($stats);

    echo "\n✅ ALL TESTS PASSED!\n";
    echo "If you see this, PHP code is working fine.\n";
    echo "Problem might be in HTML rendering or CSS.\n";

} catch (Exception $e) {
    echo "\n❌ ERROR FOUND!\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
