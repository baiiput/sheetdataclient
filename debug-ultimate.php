<?php
// ========================================
// 🔍 ULTIMATE DEBUG - CEK SEMUANYA
// ========================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre style='background: #1a202c; color: #f7fafc; padding: 20px; border-radius: 8px;'>";
echo "🔍 ULTIMATE DEBUG - FULL DIAGNOSTIC\n";
echo "=====================================\n\n";

// ========================================
// 1. CEK PHP INFO
// ========================================

echo "--- PHP ENVIRONMENT ---\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Current Script: " . __FILE__ . "\n";
echo "Working Directory: " . getcwd() . "\n\n";

// ========================================
// 2. CEK FILE FUNCTIONS.PHP
// ========================================

echo "--- FILE FUNCTIONS.PHP ---\n";

$functionsPath = __DIR__ . '/includes/functions.php';
echo "Looking for: $functionsPath\n";

if (file_exists($functionsPath)) {
    echo "✅ File exists\n";
    echo "File size: " . filesize($functionsPath) . " bytes\n";
    echo "Last modified: " . date('Y-m-d H:i:s', filemtime($functionsPath)) . "\n";

    // Read file content and check for our fix
    $content = file_get_contents($functionsPath);

    if (strpos($content, 'if ($string === null') !== false) {
        echo "✅ NULL HANDLING FOUND in e() function!\n";
    } else {
        echo "❌ NULL HANDLING NOT FOUND in e() function!\n";
        echo "   File might not be updated correctly.\n";
    }

    if (strpos($content, 'if ($text === null') !== false) {
        echo "✅ NULL HANDLING FOUND in truncate() function!\n";
    } else {
        echo "❌ NULL HANDLING NOT FOUND in truncate() function!\n";
        echo "   File might not be updated correctly.\n";
    }

    echo "\n";
} else {
    echo "❌ File NOT found!\n";
    echo "   Looking in wrong location?\n\n";
}

// ========================================
// 3. REQUIRE FILES
// ========================================

echo "--- LOADING FILES ---\n";

try {
    require_once 'includes/config.php';
    echo "✅ Config loaded\n";
} catch (Exception $e) {
    echo "❌ Config error: " . $e->getMessage() . "\n";
}

try {
    require_once 'includes/db.php';
    echo "✅ Database class loaded\n";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

try {
    require_once 'includes/functions.php';
    echo "✅ Functions loaded\n";
} catch (Exception $e) {
    echo "❌ Functions error: " . $e->getMessage() . "\n";
}

echo "\n";

// ========================================
// 4. TEST FUNGSI e() LANGSUNG
// ========================================

echo "--- TEST FUNCTION e() ---\n";

// Test with NULL
$testNull = null;
try {
    $resultNull = e($testNull);
    echo "e(null) = '" . $resultNull . "' (length: " . strlen($resultNull) . ")\n";
    if ($resultNull === '') {
        echo "✅ NULL handling works!\n";
    } else {
        echo "❌ NULL handling broken! Returned: " . var_export($resultNull, true) . "\n";
    }
} catch (Exception $e) {
    echo "❌ e(null) threw exception: " . $e->getMessage() . "\n";
}

// Test with empty string
try {
    $resultEmpty = e('');
    echo "e('') = '" . $resultEmpty . "' (length: " . strlen($resultEmpty) . ")\n";
} catch (Exception $e) {
    echo "❌ e('') threw exception: " . $e->getMessage() . "\n";
}

// Test with normal string
try {
    $resultNormal = e('test@example.com');
    echo "e('test@example.com') = '" . $resultNormal . "'\n";
} catch (Exception $e) {
    echo "❌ e('test@example.com') threw exception: " . $e->getMessage() . "\n";
}

echo "\n";

// ========================================
// 5. TEST FUNGSI truncate() LANGSUNG
// ========================================

echo "--- TEST FUNCTION truncate() ---\n";

// Test with NULL
try {
    $resultNull = truncate(null);
    echo "truncate(null) = '" . $resultNull . "' (length: " . strlen($resultNull) . ")\n";
    if ($resultNull === '') {
        echo "✅ NULL handling works!\n";
    } else {
        echo "❌ NULL handling broken!\n";
    }
} catch (Exception $e) {
    echo "❌ truncate(null) threw exception: " . $e->getMessage() . "\n";
}

// Test with normal string
try {
    $resultNormal = truncate('This is a long text that should be truncated', 20);
    echo "truncate('This is a long...', 20) = '" . $resultNormal . "'\n";
} catch (Exception $e) {
    echo "❌ truncate() threw exception: " . $e->getMessage() . "\n";
}

echo "\n";

// ========================================
// 6. TEST DENGAN DATA REAL DARI DATABASE
// ========================================

echo "--- TEST WITH REAL DATABASE DATA ---\n";

try {
    $db = Database::getInstance();
    echo "✅ Database connected\n";

    $log = $db->fetchOne("SELECT * FROM change_logs ORDER BY id DESC LIMIT 1");

    if ($log) {
        echo "✅ Got 1 record from database\n\n";

        echo "Testing e() with real data:\n";
        foreach ($log as $key => $value) {
            try {
                $escaped = e($value);
                $display = strlen($escaped) > 30 ? substr($escaped, 0, 30) . '...' : $escaped;
                echo "  {$key}: '" . $display . "' (length: " . strlen($escaped) . ")\n";
            } catch (Exception $e) {
                echo "  {$key}: ❌ ERROR: " . $e->getMessage() . "\n";
            }
        }

        echo "\n";

        echo "Raw data (without e()):\n";
        echo "  user_email: " . var_export($log['user_email'], true) . "\n";
        echo "  sheet_name: " . var_export($log['sheet_name'], true) . "\n";
        echo "  cell_address: " . var_export($log['cell_address'], true) . "\n";
        echo "  client_name: " . var_export($log['client_name'], true) . "\n";

    } else {
        echo "❌ No data in database\n";
    }

} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n";

// ========================================
// 7. CEK OPCACHE
// ========================================

echo "--- OPCACHE STATUS ---\n";

if (function_exists('opcache_get_status')) {
    $opcache = opcache_get_status();
    if ($opcache !== false) {
        echo "Opcache Enabled: " . ($opcache['opcache_enabled'] ? 'YES' : 'NO') . "\n";
        echo "Cache Full: " . ($opcache['cache_full'] ? 'YES' : 'NO') . "\n";
        echo "Cached Scripts: " . $opcache['opcache_statistics']['num_cached_scripts'] . "\n";

        // Check if functions.php is cached
        $cached_scripts = opcache_get_status(true)['scripts'] ?? [];
        $functions_cached = false;
        foreach ($cached_scripts as $script => $info) {
            if (strpos($script, 'functions.php') !== false) {
                $functions_cached = true;
                echo "functions.php in cache: $script\n";
                echo "  Last used: " . date('Y-m-d H:i:s', $info['last_used_timestamp']) . "\n";
            }
        }

        if (!$functions_cached) {
            echo "functions.php NOT in opcache (this is OK)\n";
        }
    } else {
        echo "Opcache not active\n";
    }
} else {
    echo "Opcache not available\n";
}

echo "\n";

// ========================================
// 8. SHOW FUNCTION SOURCE
// ========================================

echo "--- FUNCTION e() SOURCE CODE ---\n";

try {
    $reflection = new ReflectionFunction('e');
    $filename = $reflection->getFileName();
    $start_line = $reflection->getStartLine() - 1;
    $end_line = $reflection->getEndLine();
    $length = $end_line - $start_line;

    $source = file($filename);
    $body = implode("", array_slice($source, $start_line, $length));

    echo "Function e() from: $filename\n";
    echo "Lines: $start_line - $end_line\n\n";
    echo $body;
    echo "\n";
} catch (Exception $e) {
    echo "Could not get function source: " . $e->getMessage() . "\n";
}

echo "\n";

// ========================================
// 9. DIAGNOSTIC SUMMARY
// ========================================

echo "--- DIAGNOSTIC SUMMARY ---\n";
echo "If NULL handling is NOT found in functions.php:\n";
echo "  1. File might be in different location\n";
echo "  2. File not saved properly\n";
echo "  3. Wrong file being edited\n\n";

echo "If NULL handling IS found but still not working:\n";
echo "  1. Clear opcache: opcache_reset()\n";
echo "  2. Restart PHP-FPM\n";
echo "  3. Check for other includes of functions.php\n\n";

echo "✅ DIAGNOSTIC COMPLETE!\n";

echo "</pre>";
?>
