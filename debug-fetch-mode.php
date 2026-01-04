<?php
// ========================================
// 🔍 DEBUG FETCH MODE - CEK PDO::FETCH_ASSOC
// ========================================
// File untuk cek apakah db.php sudah menggunakan FETCH_ASSOC

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre style='background: #1a202c; color: #f7fafc; padding: 20px; border-radius: 8px;'>";
echo "🔍 DEBUG FETCH MODE - CEK PDO::FETCH_ASSOC\n";
echo "==========================================\n\n";

// ========================================
// 1. CEK SOURCE CODE db.php
// ========================================

echo "--- 1. CEK SOURCE CODE db.php ---\n\n";

$dbPath = __DIR__ . '/includes/db.php';
echo "File path: $dbPath\n";

if (file_exists($dbPath)) {
    echo "✅ File exists\n";
    echo "Last modified: " . date('Y-m-d H:i:s', filemtime($dbPath)) . "\n\n";

    $content = file_get_contents($dbPath);

    // Check for FETCH_ASSOC in fetchAll
    if (strpos($content, '->fetchAll(PDO::FETCH_ASSOC)') !== false) {
        echo "✅ FOUND: fetchAll(PDO::FETCH_ASSOC)\n";
    } else if (strpos($content, '->fetchAll()') !== false) {
        echo "❌ FOUND: fetchAll() WITHOUT PDO::FETCH_ASSOC\n";
        echo "   File NOT updated yet!\n";
    } else {
        echo "⚠️ Cannot find fetchAll method\n";
    }

    // Check for FETCH_ASSOC in fetchOne
    if (strpos($content, '->fetch(PDO::FETCH_ASSOC)') !== false) {
        echo "✅ FOUND: fetch(PDO::FETCH_ASSOC)\n";
    } else if (strpos($content, '->fetch()') !== false) {
        echo "❌ FOUND: fetch() WITHOUT PDO::FETCH_ASSOC\n";
        echo "   File NOT updated yet!\n";
    } else {
        echo "⚠️ Cannot find fetch method\n";
    }

    echo "\n";

    // Show the actual fetchAll and fetchOne methods
    echo "--- SOURCE CODE: fetchAll() method ---\n";
    preg_match('/public function fetchAll.*?\n.*?\n.*?\n}/s', $content, $matches);
    if (!empty($matches)) {
        echo $matches[0] . "\n\n";
    }

    echo "--- SOURCE CODE: fetchOne() method ---\n";
    preg_match('/public function fetchOne.*?\n.*?\n.*?\n}/s', $content, $matches);
    if (!empty($matches)) {
        echo $matches[0] . "\n\n";
    }

} else {
    echo "❌ File NOT found!\n\n";
}

// ========================================
// 2. TEST ACTUAL FETCH RESULT
// ========================================

echo "--- 2. TEST ACTUAL FETCH RESULT ---\n\n";

try {
    require_once 'includes/config.php';
    require_once 'includes/db.php';
    require_once 'includes/functions.php';

    $db = Database::getInstance();
    echo "✅ Database connected\n\n";

    echo "--- Direct Query Test ---\n";
    $sql = "SELECT * FROM change_logs ORDER BY id DESC LIMIT 1";
    $result = $db->fetchOne($sql);

    if ($result) {
        echo "✅ Got 1 record\n\n";

        echo "Array keys type check:\n";
        $firstKey = array_key_first($result);
        echo "First key: " . var_export($firstKey, true) . "\n";
        echo "First key type: " . gettype($firstKey) . "\n\n";

        if (is_string($firstKey)) {
            echo "✅ Keys are STRINGS (associative) - CORRECT!\n";
            echo "   This means db.php is updated correctly.\n\n";
        } else if (is_int($firstKey)) {
            echo "❌ Keys are INTEGERS (numeric) - WRONG!\n";
            echo "   This means db.php is NOT updated or opcache not cleared.\n\n";
        }

        echo "All keys:\n";
        print_r(array_keys($result));
        echo "\n";

        echo "Sample data access test:\n";
        if (isset($result['id'])) {
            echo "✅ result['id'] = " . $result['id'] . " (GOOD - associative access works)\n";
        } else {
            echo "❌ result['id'] = NULL (BAD - associative access doesn't work)\n";
        }

        if (isset($result[0])) {
            echo "⚠️ result[0] = " . $result[0] . " (BAD - numeric access works, means numeric keys)\n";
        } else {
            echo "✅ result[0] = NULL (GOOD - numeric access doesn't work, means associative keys)\n";
        }

        echo "\n";

    } else {
        echo "❌ No data in database\n\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

// ========================================
// 3. OPCACHE CHECK
// ========================================

echo "--- 3. OPCACHE STATUS ---\n\n";

if (function_exists('opcache_get_status')) {
    $opcache = opcache_get_status();
    if ($opcache !== false && $opcache['opcache_enabled']) {
        echo "⚠️ Opcache is ENABLED\n";
        echo "Cache full: " . ($opcache['cache_full'] ? 'YES' : 'NO') . "\n";
        echo "Cached scripts: " . $opcache['opcache_statistics']['num_cached_scripts'] . "\n\n";

        echo "🔧 SOLUTION: Restart PHP-FPM to clear opcache!\n";
        echo "   - Via aaPanel: App Store → PHP → Restart\n";
        echo "   - Via SSH: systemctl restart php-fpm\n\n";

        // Check if db.php is cached
        $cached_scripts = opcache_get_status(true)['scripts'] ?? [];
        foreach ($cached_scripts as $script => $info) {
            if (strpos($script, 'db.php') !== false) {
                echo "⚠️ db.php IS CACHED: $script\n";
                echo "   Last used: " . date('Y-m-d H:i:s', $info['last_used_timestamp']) . "\n";
                echo "   Timestamp: " . date('Y-m-d H:i:s', $info['timestamp']) . "\n\n";
                echo "   👉 You MUST restart PHP to load the new version!\n\n";
            }
        }

    } else {
        echo "✅ Opcache is disabled or not active\n\n";
    }
} else {
    echo "ℹ️ Opcache not available\n\n";
}

// ========================================
// 4. DIAGNOSTIC SUMMARY
// ========================================

echo "--- 4. DIAGNOSTIC SUMMARY ---\n\n";

echo "If db.php source shows fetchAll() WITHOUT PDO::FETCH_ASSOC:\n";
echo "  ❌ File not uploaded yet\n";
echo "  👉 Upload the new db.php from repository to VPS\n\n";

echo "If db.php source shows fetchAll(PDO::FETCH_ASSOC) BUT keys are still NUMERIC:\n";
echo "  ❌ Opcache serving old version\n";
echo "  👉 Restart PHP-FPM in aaPanel\n";
echo "  👉 Or run: systemctl restart php-fpm\n\n";

echo "If keys are STRINGS (associative):\n";
echo "  ✅ Everything is working!\n";
echo "  👉 Check index.php to see if data displays correctly\n\n";

echo "✅ DIAGNOSTIC COMPLETE!\n";

echo "</pre>";
?>
