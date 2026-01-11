<?php
// ========================================
// 🔧 DIRECT MIGRATION RUNNER (Standalone)
// ========================================
// Script alternatif untuk menjalankan migration tanpa class Database
// Gunakan ini jika run-migration.php error

require_once 'includes/config.php';

echo "🔧 Running migration: Add Account Column (Direct)\n";
echo "========================================\n\n";

try {
    // Direct PDO connection without custom class
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];

    echo "Connecting to database...\n";
    echo "  Host: " . DB_HOST . "\n";
    echo "  Database: " . DB_NAME . "\n";
    echo "  User: " . DB_USER . "\n\n";

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    $pdo->exec("SET NAMES utf8mb4");

    echo "✅ Connected successfully!\n\n";

    // Read SQL file
    $sqlFile = __DIR__ . '/migration-add-account.sql';

    if (!file_exists($sqlFile)) {
        throw new Exception("Migration file not found: $sqlFile");
    }

    echo "Reading migration file...\n";
    $sql = file_get_contents($sqlFile);

    // Execute SQL statements
    echo "Executing migration...\n\n";

    // Split by semicolon and execute each statement
    $statements = explode(';', $sql);

    foreach ($statements as $statement) {
        $statement = trim($statement);

        // Skip empty statements and comments
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }

        echo "Executing: " . substr($statement, 0, 60) . "...\n";
        $pdo->exec($statement);
    }

    echo "\n✅ Migration completed successfully!\n\n";

    // Verify the column was added
    echo "Verifying column creation...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM change_logs LIKE 'account'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($column) {
        echo "✅ Column 'account' exists!\n";
        echo "   Type: " . $column['Type'] . "\n";
        echo "   Null: " . $column['Null'] . "\n";
        echo "   Default: " . ($column['Default'] ?? 'NULL') . "\n";
    } else {
        echo "⚠️  Warning: Could not verify column creation\n";
    }

    // Show all columns
    echo "\n";
    echo "All columns in change_logs table:\n";
    echo "========================================\n";

    $stmt = $pdo->query("SHOW COLUMNS FROM change_logs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($columns as $col) {
        $marker = ($col['Field'] === 'account') ? ' ← NEW' : '';
        echo "  - " . $col['Field'] . " (" . $col['Type'] . ")" . $marker . "\n";
    }

} catch (PDOException $e) {
    echo "\n❌ Database Error!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nPossible issues:\n";
    echo "1. Check database credentials in includes/config.php\n";
    echo "2. Make sure MySQL/MariaDB is running\n";
    echo "3. Verify database name exists\n";
    echo "4. Check user permissions\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Migration failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n========================================\n";
echo "Migration complete!\n";
echo "You can now delete these files:\n";
echo "  - run-migration.php\n";
echo "  - run-migration-direct.php\n";
echo "  - migration-add-account.sql\n";
echo "========================================\n";
