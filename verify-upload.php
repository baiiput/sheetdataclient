<?php
// ========================================
// 🧪 TEST FILE UPLOAD VERIFICATION
// ========================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre style='background: #1a202c; color: #f7fafc; padding: 20px; border-radius: 8px; font-size: 14px;'>";
echo "🧪 FILE UPLOAD VERIFICATION\n";
echo "============================\n\n";

// Check db.php
$dbPath = __DIR__ . '/includes/db.php';
echo "1. Checking db.php...\n";
echo "   Path: $dbPath\n";

if (file_exists($dbPath)) {
    $lastModified = date('Y-m-d H:i:s', filemtime($dbPath));
    echo "   ✅ File exists\n";
    echo "   📅 Last modified: $lastModified\n";

    $content = file_get_contents($dbPath);

    // Check for the updated error message
    if (strpos($content, 'Database query failed: ') !== false) {
        echo "   ✅ Contains UPDATED error message (new version)\n";
    } else if (strpos($content, 'Database query failed.') !== false) {
        echo "   ❌ Contains OLD error message (old version)\n";
        echo "   👉 UPLOAD file db.php yang baru!\n";
    }

    // Check for backticks handling
    if (strpos($content, "strpos(\$table, '`')") !== false) {
        echo "   ✅ Contains backticks auto-handling (new version)\n";
    } else {
        echo "   ❌ Missing backticks handling (old version)\n";
        echo "   👉 UPLOAD file db.php yang baru!\n";
    }
} else {
    echo "   ❌ File NOT found\n";
}

echo "\n";

// Check functions.php
$funcPath = __DIR__ . '/includes/functions.php';
echo "2. Checking functions.php...\n";
echo "   Path: $funcPath\n";

if (file_exists($funcPath)) {
    $lastModified = date('Y-m-d H:i:s', filemtime($funcPath));
    echo "   ✅ File exists\n";
    echo "   📅 Last modified: $lastModified\n";

    $content = file_get_contents($funcPath);

    // Check for single-line search WHERE
    if (strpos($content, '`user_email` LIKE :search OR `sheet_name` LIKE :search') !== false) {
        echo "   ✅ Contains SINGLE-LINE WHERE clause (new version)\n";
        echo "   ✅ Searches 7 columns including user_email and sheet_name\n";
    } else if (strpos($content, 'client_name LIKE :search OR kit_number LIKE :search OR cell_address LIKE :search)') !== false) {
        echo "   ❌ Contains OLD 3-column search (old version)\n";
        echo "   👉 UPLOAD file functions.php yang baru!\n";
    }
} else {
    echo "   ❌ File NOT found\n";
}

echo "\n";
echo "============================\n";
echo "📋 SUMMARY:\n\n";

require_once 'includes/config.php';
require_once 'includes/db.php';

try {
    $db = Database::getInstance();
    echo "✅ Database connection works\n\n";

    echo "🧪 Testing simple COUNT query...\n";
    $total = $db->count('change_logs');
    echo "✅ Simple count works: $total records\n\n";

    echo "🧪 Testing COUNT with WHERE clause...\n";
    $where = "`kit_number` LIKE :search";
    $params = ['search' => '%KIT%'];
    $count = $db->count('change_logs', $where, $params);
    echo "✅ Count with WHERE works: $count matches\n\n";

    echo "🧪 Testing complex WHERE clause...\n";
    $where2 = "1=1 AND (`client_name` LIKE :search OR `kit_number` LIKE :search)";
    $params2 = ['search' => '%kit%'];

    try {
        $count2 = $db->count('change_logs', $where2, $params2);
        echo "✅ Complex WHERE works: $count2 matches\n";
        echo "✅ ALL TESTS PASSED! Search should work now.\n";
    } catch (Exception $e) {
        echo "❌ Complex WHERE FAILED: " . $e->getMessage() . "\n";
        echo "👉 This is the issue causing search to fail!\n";
    }

} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n";
echo "============================\n";
echo "💡 INSTRUCTIONS:\n\n";
echo "If you see ❌ (old version):\n";
echo "  1. Upload includes/db.php dari repository\n";
echo "  2. Upload includes/functions.php dari repository\n";
echo "  3. Refresh this page\n\n";
echo "If you see ✅ (new version) but still errors:\n";
echo "  - Check PHP error log in aaPanel\n";
echo "  - Restart PHP-FPM\n";

echo "</pre>";
?>
