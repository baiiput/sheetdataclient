<?php
// ========================================
// 🔍 DEBUG SEARCH - TEST SEARCH QUERY
// ========================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

echo "<pre style='background: #1a202c; color: #f7fafc; padding: 20px; border-radius: 8px; font-size: 13px;'>";
echo "🔍 DEBUG SEARCH - TEST SEARCH QUERY\n";
echo "=====================================\n\n";

$searchTerm = $_GET['search'] ?? 'kit';

echo "Search term: '$searchTerm'\n\n";

try {
    $db = Database::getInstance();
    echo "✅ Database connected\n\n";

    // Test 1: Count all records
    echo "--- TEST 1: Total records in database ---\n";
    $totalRecords = $db->count('`change_logs`');
    echo "Total records: $totalRecords\n\n";

    // Test 2: Show sample data
    echo "--- TEST 2: Sample data (first 3 records) ---\n";
    $sampleData = $db->fetchAll("SELECT `id`, `client_name`, `kit_number`, `cell_address`, `user_email`, `sheet_name`, `old_value`, `new_value` FROM `change_logs` LIMIT 3");

    foreach ($sampleData as $row) {
        echo "ID: {$row['id']}\n";
        echo "  client_name: " . ($row['client_name'] ?? 'NULL') . "\n";
        echo "  kit_number: " . ($row['kit_number'] ?? 'NULL') . "\n";
        echo "  cell_address: " . ($row['cell_address'] ?? 'NULL') . "\n";
        echo "  user_email: " . ($row['user_email'] ?? 'NULL') . "\n";
        echo "  sheet_name: " . ($row['sheet_name'] ?? 'NULL') . "\n";
        echo "  old_value: " . ($row['old_value'] ?? 'NULL') . "\n";
        echo "  new_value: " . ($row['new_value'] ?? 'NULL') . "\n";
        echo "\n";
    }

    // Test 3: Search each column individually
    echo "--- TEST 3: Search each column individually for '$searchTerm' ---\n";

    $columns = ['client_name', 'kit_number', 'cell_address', 'user_email', 'sheet_name', 'old_value', 'new_value'];

    foreach ($columns as $col) {
        $sql = "SELECT COUNT(*) as total FROM `change_logs` WHERE `$col` LIKE :search";
        $result = $db->fetchOne($sql, ['search' => '%' . $searchTerm . '%']);
        $count = $result['total'];
        echo "  $col: $count matches\n";
    }
    echo "\n";

    // Test 4: Combined search (like in getChangeLogs)
    echo "--- TEST 4: Combined search (actual query from getChangeLogs) ---\n";

    $where = ['1=1'];
    $params = [];

    $where[] = '(`client_name` LIKE :search OR `kit_number` LIKE :search OR `cell_address` LIKE :search OR `user_email` LIKE :search OR `sheet_name` LIKE :search OR `old_value` LIKE :search OR `new_value` LIKE :search)';
    $params['search'] = '%' . $searchTerm . '%';

    $whereClause = implode(' AND ', $where);

    echo "WHERE clause: $whereClause\n";
    echo "Parameters: " . print_r($params, true) . "\n";

    $totalMatches = $db->count('change_logs', $whereClause, $params);
    echo "Total matches: $totalMatches\n\n";

    // Test 5: Get actual results
    echo "--- TEST 5: Actual search results (first 5) ---\n";

    $sql = "SELECT * FROM `change_logs` WHERE {$whereClause} ORDER BY `changed_at` DESC LIMIT 5";
    $results = $db->fetchAll($sql, $params);

    echo "Found " . count($results) . " results\n\n";

    foreach ($results as $row) {
        echo "ID: {$row['id']} | {$row['changed_at']}\n";
        echo "  User: {$row['user_email']}\n";
        echo "  Sheet: {$row['sheet_name']}\n";
        echo "  Cell: {$row['cell_address']}\n";
        echo "  Client: " . ($row['client_name'] ?? '-') . "\n";
        echo "  KIT: " . ($row['kit_number'] ?? '-') . "\n";
        echo "  Old: " . ($row['old_value'] ?? '-') . "\n";
        echo "  New: " . ($row['new_value'] ?? '-') . "\n";
        echo "\n";
    }

    // Test 6: Test with getChangeLogs function
    echo "--- TEST 6: Test getChangeLogs() function ---\n";

    $filters = [
        'search' => $searchTerm,
        'sheet_name' => '',
        'user_email' => '',
        'date_from' => '',
        'date_to' => ''
    ];

    $result = getChangeLogs($filters, 1, 50);

    echo "getChangeLogs() returned:\n";
    echo "  Total records: {$result['pagination']['total_records']}\n";
    echo "  Logs count: " . count($result['logs']) . "\n\n";

    if (count($result['logs']) > 0) {
        echo "First result:\n";
        $first = $result['logs'][0];
        echo "  ID: {$first['id']}\n";
        echo "  Client: " . ($first['client_name'] ?? '-') . "\n";
        echo "  KIT: " . ($first['kit_number'] ?? '-') . "\n";
        echo "  Cell: {$first['cell_address']}\n";
    } else {
        echo "❌ No results from getChangeLogs()\n";
    }

    echo "\n";
    echo "=====================================\n";
    echo "✅ DEBUG COMPLETE\n";
    echo "\nTry different search terms:\n";
    echo "  ?search=kit\n";
    echo "  ?search=golden\n";
    echo "  ?search=client\n";
    echo "  ?search=K190\n";
    echo "  ?search=gmail\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
