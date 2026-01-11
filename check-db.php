<?php
// ========================================
// 🔍 DATABASE STATUS CHECKER
// ========================================
// Script untuk mengecek status kolom account di database

require_once 'includes/config.php';
require_once 'includes/db.php';

echo "🔍 Checking Database Status\n";
echo "========================================\n\n";

try {
    $pdo = Database::getInstance()->getConnection();

    // Check if account column exists
    echo "Checking for 'account' column in change_logs table...\n";

    $stmt = $pdo->query("SHOW COLUMNS FROM change_logs LIKE 'account'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($column) {
        echo "✅ SUCCESS: Column 'account' EXISTS\n";
        echo "   Type: " . $column['Type'] . "\n";
        echo "   Null: " . $column['Null'] . "\n";
        echo "   Default: " . ($column['Default'] ?? 'NULL') . "\n";
        echo "\n✓ Database is ready!\n";
        echo "✓ You can safely use the dashboard.\n";
    } else {
        echo "❌ WARNING: Column 'account' DOES NOT EXIST\n";
        echo "\n⚠️  You need to run the migration!\n";
        echo "Run this command:\n";
        echo "  php run-migration.php\n";
    }

    // Show all columns for reference
    echo "\n========================================\n";
    echo "All columns in change_logs table:\n";
    echo "========================================\n";

    $stmt = $pdo->query("SHOW COLUMNS FROM change_logs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }

} catch (Exception $e) {
    echo "\n❌ Database Error!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nPlease check your database configuration in includes/config.php\n";
    exit(1);
}

echo "\n========================================\n";
