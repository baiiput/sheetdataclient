<?php
// ========================================
// 🛠️ HELPER FUNCTIONS
// ========================================

/**
 * Start secure session
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Require login (redirect to login if not authenticated)
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Get current logged in user
 */
function getCurrentUser() {
    startSession();
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'name' => $_SESSION['name'] ?? null
    ];
}

/**
 * Login user
 */
function loginUser($username, $password) {
    try {
        $db = Database::getInstance();

        $user = $db->fetchOne(
            "SELECT * FROM admin_users WHERE username = :username AND is_active = 1",
            ['username' => $username]
        );

        if (!$user) {
            return ['success' => false, 'message' => 'Username atau password salah'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Username atau password salah'];
        }

        // Update last login
        $db->update(
            'admin_users',
            ['last_login' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $user['id']]
        );

        // Set session
        startSession();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['name'] = $user['name'];

        return ['success' => true, 'message' => 'Login berhasil'];

    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Terjadi kesalahan sistem'];
    }
}

/**
 * Logout user
 */
function logoutUser() {
    startSession();
    $_SESSION = [];
    session_destroy();
    setcookie(SESSION_NAME, '', time() - 3600, '/');
}

/**
 * Escape HTML (with NULL handling)
 */
function e($string) {
    if ($string === null || $string === '') {
        return '';
    }
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Format date to Indonesian
 */
function formatDate($date, $format = null) {
    if (empty($date)) {
        return '-';
    }

    if (is_string($date)) {
        $date = new DateTime($date);
    }

    $format = $format ?? DATE_FORMAT;
    return $date->format($format);
}

/**
 * Format relative time (2 hours ago, 3 days ago, etc)
 */
function timeAgo($datetime) {
    if (empty($datetime)) {
        return '-';
    }

    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return $diff . ' detik yang lalu';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' menit yang lalu';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' jam yang lalu';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' hari yang lalu';
    } else {
        return formatDate($datetime);
    }
}

/**
 * Get pagination data
 */
function getPagination($totalRecords, $page = 1, $perPage = null) {
    $perPage = $perPage ?? RECORDS_PER_PAGE;
    $totalPages = ceil($totalRecords / $perPage);
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;

    return [
        'total_records' => $totalRecords,
        'per_page' => $perPage,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_prev' => $page > 1,
        'has_next' => $page < $totalPages
    ];
}

/**
 * Get all change logs with filters
 */
function getChangeLogs($filters = [], $page = 1, $perPage = null) {
    $db = Database::getInstance();
    $perPage = $perPage ?? RECORDS_PER_PAGE;

    // Build WHERE clause
    $where = ['1=1'];
    $params = [];

    if (!empty($filters['sheet_name'])) {
        $where[] = 'sheet_name = :sheet_name';
        $params['sheet_name'] = $filters['sheet_name'];
    }

    if (!empty($filters['user_email'])) {
        $where[] = 'user_email = :user_email';
        $params['user_email'] = $filters['user_email'];
    }

    if (!empty($filters['date_from'])) {
        $where[] = 'DATE(changed_at) >= :date_from';
        $params['date_from'] = $filters['date_from'];
    }

    if (!empty($filters['date_to'])) {
        $where[] = 'DATE(changed_at) <= :date_to';
        $params['date_to'] = $filters['date_to'];
    }

    if (!empty($filters['search'])) {
        $where[] = '(client_name LIKE :search OR kit_number LIKE :search OR cell_address LIKE :search)';
        $params['search'] = '%' . $filters['search'] . '%';
    }

    $whereClause = implode(' AND ', $where);

    // Get total count
    $totalRecords = $db->count('change_logs', $whereClause, $params);

    // Get pagination
    $pagination = getPagination($totalRecords, $page, $perPage);

    // Get data
    $sql = "SELECT * FROM change_logs
            WHERE {$whereClause}
            ORDER BY changed_at DESC
            LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";

    $logs = $db->fetchAll($sql, $params);

    return [
        'logs' => $logs,
        'pagination' => $pagination
    ];
}

/**
 * Get unique sheet names
 */
function getSheetNames() {
    $db = Database::getInstance();
    $sql = "SELECT DISTINCT sheet_name FROM change_logs ORDER BY sheet_name";
    $results = $db->fetchAll($sql);
    return array_column($results, 'sheet_name');
}

/**
 * Get unique user emails
 */
function getUserEmails() {
    $db = Database::getInstance();
    $sql = "SELECT DISTINCT user_email FROM change_logs ORDER BY user_email";
    $results = $db->fetchAll($sql);
    return array_column($results, 'user_email');
}

/**
 * Get dashboard statistics
 */
function getDashboardStats() {
    $db = Database::getInstance();

    // Total changes
    $totalChanges = $db->count('change_logs');

    // Changes today
    $changesToday = $db->count('change_logs', 'DATE(changed_at) = CURDATE()');

    // Changes this week
    $changesThisWeek = $db->count('change_logs', 'YEARWEEK(changed_at) = YEARWEEK(NOW())');

    // Changes this month
    $changesThisMonth = $db->count('change_logs', 'YEAR(changed_at) = YEAR(NOW()) AND MONTH(changed_at) = MONTH(NOW())');

    // Unique users
    $uniqueUsers = $db->fetchOne("SELECT COUNT(DISTINCT user_email) as total FROM change_logs")['total'];

    // Unique sheets
    $uniqueSheets = $db->fetchOne("SELECT COUNT(DISTINCT sheet_name) as total FROM change_logs")['total'];

    // Most active user today
    $mostActiveUser = $db->fetchOne(
        "SELECT user_email, COUNT(*) as total
         FROM change_logs
         WHERE DATE(changed_at) = CURDATE()
         GROUP BY user_email
         ORDER BY total DESC
         LIMIT 1"
    );

    // Most modified sheet today
    $mostModifiedSheet = $db->fetchOne(
        "SELECT sheet_name, COUNT(*) as total
         FROM change_logs
         WHERE DATE(changed_at) = CURDATE()
         GROUP BY sheet_name
         ORDER BY total DESC
         LIMIT 1"
    );

    return [
        'total_changes' => $totalChanges,
        'changes_today' => $changesToday,
        'changes_this_week' => $changesThisWeek,
        'changes_this_month' => $changesThisMonth,
        'unique_users' => $uniqueUsers,
        'unique_sheets' => $uniqueSheets,
        'most_active_user' => $mostActiveUser,
        'most_modified_sheet' => $mostModifiedSheet
    ];
}

/**
 * Get change type badge class
 */
function getChangeTypeBadge($oldValue, $newValue) {
    if (empty($oldValue) && !empty($newValue)) {
        return 'badge-success';
    } elseif (!empty($oldValue) && empty($newValue)) {
        return 'badge-danger';
    } else {
        return 'badge-warning';
    }
}

/**
 * Get change type label
 */
function getChangeTypeLabel($oldValue, $newValue) {
    if (empty($oldValue) && !empty($newValue)) {
        return 'INSERT';
    } elseif (!empty($oldValue) && empty($newValue)) {
        return 'DELETE';
    } else {
        return 'UPDATE';
    }
}

/**
 * Truncate text (with NULL handling)
 */
function truncate($text, $length = 50, $suffix = '...') {
    if ($text === null || $text === '') {
        return '';
    }
    $text = (string)$text;
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Get daily activity chart data (last 7 days)
 */
function getDailyActivityData($days = 7) {
    $db = Database::getInstance();

    $sql = "SELECT DATE(changed_at) as date, COUNT(*) as total
            FROM change_logs
            WHERE changed_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
            GROUP BY DATE(changed_at)
            ORDER BY date ASC";

    return $db->fetchAll($sql, ['days' => $days]);
}

/**
 * Export to CSV
 */
function exportToCSV($logs, $filename = 'change_logs.csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Headers
    fputcsv($output, [
        'ID', 'Tanggal', 'User', 'Sheet', 'Cell', 'Nilai Lama', 'Nilai Baru',
        'Nama Client', 'KIT Number', 'Tipe'
    ]);

    // Data
    foreach ($logs as $log) {
        fputcsv($output, [
            $log['id'],
            $log['changed_at'],
            $log['user_email'],
            $log['sheet_name'],
            $log['cell_address'],
            $log['old_value'],
            $log['new_value'],
            $log['client_name'],
            $log['kit_number'],
            getChangeTypeLabel($log['old_value'], $log['new_value'])
        ]);
    }

    fclose($output);
    exit;
}
