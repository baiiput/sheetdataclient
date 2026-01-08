<?php
// ========================================
// 📡 API ENDPOINT: Track Change
// ========================================
// Endpoint untuk menerima data perubahan dari Google Apps Script

// Load configuration
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// ========================================
// 🔐 CORS & SECURITY HEADERS
// ========================================

// Set CORS headers untuk Google Apps Script
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ========================================
// 📥 RECEIVE & VALIDATE REQUEST
// ========================================

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(405, 'error', 'Method not allowed. Use POST.');
}

// Check if API is enabled
if (!API_ENABLED) {
    sendResponse(403, 'error', 'API is disabled.');
}

// Validate API Key (jika diaktifkan)
if (API_REQUIRE_AUTH) {
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
    if ($apiKey !== API_KEY) {
        logRequest('Unauthorized API request', 401);
        sendResponse(401, 'error', 'Invalid API key.');
    }
}

// Get JSON input
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

// Validate JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    logRequest('Invalid JSON: ' . json_last_error_msg(), 400);
    sendResponse(400, 'error', 'Invalid JSON: ' . json_last_error_msg());
}

// ========================================
// ✅ VALIDATE REQUIRED FIELDS
// ========================================

$requiredFields = [
    'spreadsheet_id',
    'spreadsheet_name',
    'sheet_name',
    'user_email',
    'timestamp',
    'row_number',
    'column_number',
    'column_name'
];

$missingFields = [];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || $data[$field] === '') {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    sendResponse(400, 'error', 'Missing required fields: ' . implode(', ', $missingFields));
}

// ========================================
// 🧹 SANITIZE & PREPARE DATA
// ========================================

try {
    // Get client IP
    $ipAddress = getClientIP();

    // Prepare data untuk insert (FIXED: kolom row_num & column_num + action_type)
    $insertData = [
        'spreadsheet_id'   => sanitize($data['spreadsheet_id']),
        'spreadsheet_name' => sanitize($data['spreadsheet_name']),
        'sheet_name'       => sanitize($data['sheet_name']),
        'user_email'       => sanitize($data['user_email']),
        'changed_at'       => sanitize($data['timestamp']),
        'row_num'          => (int) $data['row_number'],
        'column_num'       => (int) $data['column_number'],
        'column_name'      => sanitize($data['column_name']),
        'cell_address'     => sanitize($data['column_name'] . $data['row_number']),
        'old_value'        => isset($data['old_value']) ? sanitize($data['old_value']) : null,
        'new_value'        => isset($data['new_value']) ? sanitize($data['new_value']) : null,
        'client_name'      => isset($data['client_name']) ? sanitize($data['client_name']) : null,
        'kit_number'       => isset($data['kit_number']) ? sanitize($data['kit_number']) : null,
        'action_type'      => isset($data['action_type']) ? sanitize($data['action_type']) : 'UPDATE',
        'ip_address'       => $ipAddress
    ];

    // ========================================
    // 💾 INSERT TO DATABASE
    // ========================================

    $db = Database::getInstance();
    $insertId = $db->insert('change_logs', $insertData);

    // Update user last activity (jika tabel users digunakan)
    updateUserActivity($data['user_email']);

    // Log successful insert
    logRequest("Change logged successfully (ID: {$insertId})", 200, $data);

    // ========================================
    // ✅ SUCCESS RESPONSE
    // ========================================

    sendResponse(200, 'success', 'Change logged successfully', [
        'log_id' => $insertId,
        'sheet' => $data['sheet_name'],
        'user' => $data['user_email'],
        'cell' => $data['column_name'] . $data['row_number']
    ]);

} catch (Exception $e) {
    logRequest('Database error: ' . $e->getMessage(), 500, $data);
    sendResponse(500, 'error', 'Failed to log change: ' . $e->getMessage());
}

// ========================================
// 🛠️ HELPER FUNCTIONS
// ========================================

/**
 * Send JSON response
 */
function sendResponse($statusCode, $status, $message, $data = null) {
    http_response_code($statusCode);

    $response = [
        'status' => $status,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Sanitize input
 */
function sanitize($value) {
    if (is_null($value)) {
        return null;
    }
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

/**
 * Get client IP address
 */
function getClientIP() {
    $ipKeys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];

    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Log API request
 */
function logRequest($message, $statusCode, $data = null) {
    if (!LOG_ENABLED) {
        return;
    }

    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'status_code' => $statusCode,
        'message' => $message,
        'ip' => getClientIP(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];

    if (DEBUG_MODE && $data !== null) {
        $logEntry['data'] = $data;
    }

    $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    file_put_contents(LOG_FILE, $logLine, FILE_APPEND);
}

/**
 * Update user last activity
 */
function updateUserActivity($email) {
    try {
        $db = Database::getInstance();

        // Check if user exists
        $user = $db->fetchOne(
            "SELECT id FROM users WHERE email = :email",
            ['email' => $email]
        );

        if ($user) {
            // Update existing user
            $db->update(
                'users',
                ['last_activity' => date('Y-m-d H:i:s')],
                'email = :email',
                ['email' => $email]
            );
        } else {
            // Insert new user
            $db->insert('users', [
                'email' => $email,
                'last_activity' => date('Y-m-d H:i:s')
            ]);
        }
    } catch (Exception $e) {
        // Silent fail - tidak perlu mengganggu tracking utama
        if (DEBUG_MODE) {
            error_log('Failed to update user activity: ' . $e->getMessage());
        }
    }
}
