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
    try {
        $db = Database::getInstance();
        $perPage = $perPage ?? RECORDS_PER_PAGE;

        // Ensure $page is valid
        $page = max(1, (int)$page);
        $perPage = max(1, min(1000, (int)$perPage)); // Max 1000 records per page

        // Build WHERE clause
        $where = ['1=1'];
        $params = [];

        // ✅ NEW: Sheet filter with checkbox (array support)
        if (!empty($filters['sheets']) && is_array($filters['sheets'])) {
            $placeholders = [];
            foreach ($filters['sheets'] as $i => $sheet) {
                $key = 'sheet_' . $i;
                $placeholders[] = ':' . $key;
                $params[$key] = $sheet;
            }
            $where[] = '`sheet_name` IN (' . implode(', ', $placeholders) . ')';
        } elseif (!empty($filters['sheet_name'])) {
            // Backward compatibility with old dropdown filter
            $where[] = '`sheet_name` = :sheet_name';
            $params['sheet_name'] = $filters['sheet_name'];
        }

        // ✅ NEW: Action type filter with checkbox (array support)
        if (!empty($filters['action_types']) && is_array($filters['action_types'])) {
            $placeholders = [];
            foreach ($filters['action_types'] as $i => $type) {
                $key = 'action_' . $i;
                $placeholders[] = ':' . $key;
                $params[$key] = $type;
            }
            $where[] = '`action_type` IN (' . implode(', ', $placeholders) . ')';
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(`changed_at`) >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(`changed_at`) <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $where[] = '(`client_name` LIKE :search1 OR `kit_number` LIKE :search2 OR `cell_address` LIKE :search3 OR `user_email` LIKE :search4 OR `sheet_name` LIKE :search5 OR `old_value` LIKE :search6 OR `new_value` LIKE :search7)';
            $params['search1'] = $searchTerm;
            $params['search2'] = $searchTerm;
            $params['search3'] = $searchTerm;
            $params['search4'] = $searchTerm;
            $params['search5'] = $searchTerm;
            $params['search6'] = $searchTerm;
            $params['search7'] = $searchTerm;
        }

        $whereClause = implode(' AND ', $where);

        // Get total count
        $totalRecords = $db->count('change_logs', $whereClause, $params);

        // Get pagination
        $pagination = getPagination($totalRecords, $page, $perPage);

        // Get data
        $sql = "SELECT * FROM `change_logs`
                WHERE {$whereClause}
                ORDER BY `changed_at` DESC
                LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";

        $logs = $db->fetchAll($sql, $params);

        return [
            'logs' => $logs ?? [],
            'pagination' => $pagination
        ];
    } catch (Exception $e) {
        // Log error
        error_log("Error in getChangeLogs: " . $e->getMessage());

        // Return empty result
        return [
            'logs' => [],
            'pagination' => [
                'total_records' => 0,
                'per_page' => $perPage ?? RECORDS_PER_PAGE,
                'current_page' => 1,
                'total_pages' => 1,
                'offset' => 0,
                'has_prev' => false,
                'has_next' => false
            ]
        ];
    }
}

/**
 * Get unique sheet names
 */
function getSheetNames() {
    try {
        $db = Database::getInstance();
        $sql = "SELECT DISTINCT `sheet_name` FROM `change_logs` WHERE `sheet_name` IS NOT NULL AND `sheet_name` != '' ORDER BY `sheet_name`";
        $results = $db->fetchAll($sql);
        return array_column($results, 'sheet_name');
    } catch (Exception $e) {
        error_log("Error in getSheetNames: " . $e->getMessage());
        return [];
    }
}

/**
 * Get unique action types
 */
function getActionTypes() {
    try {
        $db = Database::getInstance();
        $sql = "SELECT DISTINCT `action_type` FROM `change_logs` WHERE `action_type` IS NOT NULL AND `action_type` != '' ORDER BY `action_type`";
        $results = $db->fetchAll($sql);
        return array_column($results, 'action_type');
    } catch (Exception $e) {
        error_log("Error in getActionTypes: " . $e->getMessage());
        return ['UPDATE', 'INSERT', 'DELETE']; // Default fallback
    }
}

/**
 * Get unique user emails
 */
function getUserEmails() {
    try {
        $db = Database::getInstance();
        $sql = "SELECT DISTINCT `user_email` FROM `change_logs` WHERE `user_email` IS NOT NULL AND `user_email` != '' ORDER BY `user_email`";
        $results = $db->fetchAll($sql);
        return array_column($results, 'user_email');
    } catch (Exception $e) {
        error_log("Error in getUserEmails: " . $e->getMessage());
        return [];
    }
}

/**
 * Get dashboard statistics
 */
function getDashboardStats() {
    try {
        $db = Database::getInstance();

        // Total changes
        $totalChanges = $db->count('change_logs');

        // Changes today
        $changesToday = $db->count('change_logs', 'DATE(`changed_at`) = CURDATE()');

        // Changes this week
        $changesThisWeek = $db->count('change_logs', 'YEARWEEK(`changed_at`) = YEARWEEK(NOW())');

        // Changes this month
        $changesThisMonth = $db->count('change_logs', 'YEAR(`changed_at`) = YEAR(NOW()) AND MONTH(`changed_at`) = MONTH(NOW())');

        // Unique users
        $uniqueUsers = $db->fetchOne("SELECT COUNT(DISTINCT `user_email`) as total FROM `change_logs`")['total'] ?? 0;

        // Unique sheets
        $uniqueSheets = $db->fetchOne("SELECT COUNT(DISTINCT `sheet_name`) as total FROM `change_logs`")['total'] ?? 0;

        // Most active user today
        $mostActiveUser = $db->fetchOne(
            "SELECT `user_email`, COUNT(*) as total
             FROM `change_logs`
             WHERE DATE(`changed_at`) = CURDATE()
             GROUP BY `user_email`
             ORDER BY total DESC
             LIMIT 1"
        );

        // Most modified sheet today
        $mostModifiedSheet = $db->fetchOne(
            "SELECT `sheet_name`, COUNT(*) as total
             FROM `change_logs`
             WHERE DATE(`changed_at`) = CURDATE()
             GROUP BY `sheet_name`
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
    } catch (Exception $e) {
        error_log("Error in getDashboardStats: " . $e->getMessage());
        return [
            'total_changes' => 0,
            'changes_today' => 0,
            'changes_this_week' => 0,
            'changes_this_month' => 0,
            'unique_users' => 0,
            'unique_sheets' => 0,
            'most_active_user' => null,
            'most_modified_sheet' => null
        ];
    }
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
