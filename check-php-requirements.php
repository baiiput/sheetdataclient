<?php
// ========================================
// 🔍 PHP REQUIREMENTS CHECKER
// ========================================
// Script untuk mengecek apakah semua requirement PHP sudah terpenuhi

echo "🔍 PHP Requirements Checker\n";
echo "========================================\n\n";

$errors = [];
$warnings = [];

// Check PHP version
echo "PHP Version: " . PHP_VERSION . "\n";
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    $errors[] = "PHP version must be 7.4 or higher. Current: " . PHP_VERSION;
} else {
    echo "✅ PHP version OK\n";
}

echo "\n";

// Check required extensions
$requiredExtensions = [
    'pdo' => 'PDO (PHP Data Objects)',
    'pdo_mysql' => 'PDO MySQL Driver',
    'json' => 'JSON',
    'mbstring' => 'Multibyte String',
    'curl' => 'cURL (for API calls)'
];

echo "Checking Required Extensions:\n";
echo "----------------------------------------\n";

foreach ($requiredExtensions as $ext => $name) {
    if (extension_loaded($ext)) {
        echo "✅ $name ($ext)\n";
    } else {
        echo "❌ $name ($ext) - NOT INSTALLED\n";
        $errors[] = "Extension '$ext' is required but not installed";
    }
}

// Check PDO drivers
echo "\n";
echo "Available PDO Drivers:\n";
echo "----------------------------------------\n";
if (extension_loaded('pdo')) {
    $drivers = PDO::getAvailableDrivers();
    if (empty($drivers)) {
        echo "⚠️  No PDO drivers found!\n";
        $errors[] = "No PDO drivers installed";
    } else {
        foreach ($drivers as $driver) {
            echo "  - $driver";
            if ($driver === 'mysql') {
                echo " ✅\n";
            } else {
                echo "\n";
            }
        }
        if (!in_array('mysql', $drivers)) {
            $errors[] = "PDO MySQL driver not found";
        }
    }
} else {
    echo "❌ PDO extension not loaded\n";
}

// Check optional but recommended extensions
echo "\n";
echo "Optional Extensions:\n";
echo "----------------------------------------\n";

$optionalExtensions = [
    'openssl' => 'OpenSSL (for secure connections)',
    'session' => 'Session support'
];

foreach ($optionalExtensions as $ext => $name) {
    if (extension_loaded($ext)) {
        echo "✅ $name ($ext)\n";
    } else {
        echo "⚠️  $name ($ext) - recommended but not required\n";
        $warnings[] = "Extension '$ext' is recommended";
    }
}

// Summary
echo "\n";
echo "========================================\n";
echo "SUMMARY\n";
echo "========================================\n";

if (empty($errors)) {
    echo "✅ All requirements met!\n";
    echo "You can proceed with running the application.\n";
} else {
    echo "❌ ERRORS FOUND (" . count($errors) . "):\n";
    foreach ($errors as $i => $error) {
        echo "  " . ($i + 1) . ". $error\n";
    }

    echo "\n";
    echo "🔧 TO FIX:\n";
    echo "Run these commands on your VPS:\n\n";

    if (strpos(PHP_VERSION, '7.4') !== false) {
        echo "  sudo apt update\n";
        echo "  sudo apt install -y php7.4-mysql php7.4-mbstring php7.4-curl\n";
        echo "  sudo systemctl restart php7.4-fpm\n";
    } elseif (strpos(PHP_VERSION, '8.0') !== false) {
        echo "  sudo apt update\n";
        echo "  sudo apt install -y php8.0-mysql php8.0-mbstring php8.0-curl\n";
        echo "  sudo systemctl restart php8.0-fpm\n";
    } elseif (strpos(PHP_VERSION, '8.1') !== false) {
        echo "  sudo apt update\n";
        echo "  sudo apt install -y php8.1-mysql php8.1-mbstring php8.1-curl\n";
        echo "  sudo systemctl restart php8.1-fpm\n";
    } elseif (strpos(PHP_VERSION, '8.2') !== false) {
        echo "  sudo apt update\n";
        echo "  sudo apt install -y php8.2-mysql php8.2-mbstring php8.2-curl\n";
        echo "  sudo systemctl restart php8.2-fpm\n";
    } else {
        echo "  sudo apt update\n";
        echo "  sudo apt install -y php-mysql php-mbstring php-curl\n";
        echo "  sudo systemctl restart php-fpm\n";
    }

    echo "\nThen run this script again to verify.\n";
}

if (!empty($warnings)) {
    echo "\n⚠️  WARNINGS (" . count($warnings) . "):\n";
    foreach ($warnings as $i => $warning) {
        echo "  " . ($i + 1) . ". $warning\n";
    }
}

echo "\n========================================\n";
exit(empty($errors) ? 0 : 1);
