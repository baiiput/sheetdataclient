<?php
// ========================================
// 🔧 MIGRATION RUNNER
// ========================================
// Script untuk menjalankan migration-add-account.sql

require_once 'includes/config.php';
require_once 'includes/db.php';

echo "🔧 Running migration: Add Account Column\n";
echo "========================================\n\n";

try {
    $pdo = Database::getInstance()->getConnection();

    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/migration-add-account.sql');

    // Remove comments and split by semicolon
    $statements = array_filter(
        array_map('trim',
            preg_split('/;[\s]*\n/',
                preg_replace('/--[^\n]*\n/', '', $sql)
            )
        )
    );

    foreach ($statements as $statement) {
        if (empty($statement)) continue;

        echo "Executing: " . substr($statement, 0, 60) . "...\n";
        $pdo->exec($statement);
    }

    echo "\n✅ Migration completed successfully!\n";
    echo "Column 'account' has been added to change_logs table.\n\n";

    // Verify the column was added
    $stmt = $pdo->query("SHOW COLUMNS FROM change_logs LIKE 'account'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($column) {
        echo "✓ Verified: Column 'account' exists\n";
        echo "  Type: " . $column['Type'] . "\n";
        echo "  Null: " . $column['Null'] . "\n";
        echo "  Default: " . ($column['Default'] ?? 'NULL') . "\n";
    } else {
        echo "⚠️ Warning: Could not verify column creation\n";
    }

} catch (PDOException $e) {
    echo "\n❌ Migration failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n========================================\n";
echo "Migration complete. You can now delete this file.\n";
