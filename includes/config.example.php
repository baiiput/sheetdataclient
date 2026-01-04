<?php
// ========================================
// 🔧 CONFIGURATION FILE - EXAMPLE
// ========================================
// COPY file ini menjadi config.php dan sesuaikan dengan setting Anda

// ========================================
// 🗄️ DATABASE CONFIGURATION
// ========================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'sheet_tracking');
define('DB_USER', 'sheet_tracker');
define('DB_PASS', 'YOUR_PASSWORD_HERE');  // ← GANTI dengan password database Anda
define('DB_CHARSET', 'utf8mb4');

// ========================================
// 🔐 SECURITY CONFIGURATION
// ========================================

define('SESSION_NAME', 'sheet_tracker_session');
define('SESSION_LIFETIME', 3600 * 8); // 8 jam

define('API_ENABLED', true);
define('API_REQUIRE_AUTH', false); // Set true untuk mengaktifkan API key
define('API_KEY', 'your-secret-api-key-here'); // Ganti dengan API key random yang panjang

define('ALLOWED_ORIGINS', [
    'https://script.google.com',
    'https://accounts.google.com'
]);

// ========================================
// 🌐 APPLICATION CONFIGURATION
// ========================================

date_default_timezone_set('Asia/Jakarta');

define('APP_NAME', 'Sheet Tracking System');
define('APP_VERSION', '1.0.0');
define('RECORDS_PER_PAGE', 50);
define('DATE_FORMAT', 'd/m/Y H:i:s');
define('DATE_FORMAT_SHORT', 'd/m/Y');

// ========================================
// 🎨 UI CONFIGURATION
// ========================================

define('DEFAULT_THEME', 'dark'); // 'light' or 'dark'
define('PAGE_SIZE_OPTIONS', [25, 50, 100, 200]);

// ========================================
// 🔍 LOGGING & DEBUG
// ========================================

define('DEBUG_MODE', false); // Set true untuk development, false untuk production
define('LOG_ENABLED', true);
define('LOG_FILE', __DIR__ . '/../logs/app.log');

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', 0);
}

// ========================================
// 📁 PATH CONFIGURATION
// ========================================

define('BASE_PATH', dirname(__DIR__));
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('ASSETS_PATH', BASE_PATH . '/assets');
define('LOGS_PATH', BASE_PATH . '/logs');

// ========================================
// 🛡️ SECURITY HEADERS
// ========================================

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// ========================================
// ✅ CREATE LOGS DIRECTORY
// ========================================

if (LOG_ENABLED && !is_dir(LOGS_PATH)) {
    mkdir(LOGS_PATH, 0755, true);
}
