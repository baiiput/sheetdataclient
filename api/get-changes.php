<?php
/**
 * API Endpoint for fetching change logs as JSON
 * Used for auto-refresh functionality
 */

// Prevent any output before JSON
ob_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Clear any output from includes
ob_end_clean();

// Set JSON header
header('Content-Type: application/json');

// Start session using the same method as main application
startSession();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    // Get filters from request
    $filters = [
        'search' => isset($_GET['search']) ? trim($_GET['search']) : '',
        'sheet_name' => isset($_GET['sheet']) ? trim($_GET['sheet']) : '',
        'date_from' => isset($_GET['date_from']) ? trim($_GET['date_from']) : date('Y-m-01'),
        'date_to' => isset($_GET['date_to']) ? trim($_GET['date_to']) : date('Y-m-t'),
    ];

    // Get pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 50;
    $offset = ($page - 1) * $perPage;

    // Build query
    $query = "SELECT * FROM change_logs WHERE 1=1";
    $params = [];

    if (!empty($filters['search'])) {
        $query .= " AND (client_name LIKE :search OR kit_number LIKE :search2 OR cell_address LIKE :search3)";
        $searchTerm = '%' . $filters['search'] . '%';
        $params['search'] = $searchTerm;
        $params['search2'] = $searchTerm;
        $params['search3'] = $searchTerm;
    }

    if (!empty($filters['sheet_name'])) {
        $query .= " AND sheet_name = :sheet_name";
        $params['sheet_name'] = $filters['sheet_name'];
    }

    if (!empty($filters['date_from'])) {
        $query .= " AND DATE(changed_at) >= :date_from";
        $params['date_from'] = $filters['date_from'];
    }

    if (!empty($filters['date_to'])) {
        $query .= " AND DATE(changed_at) <= :date_to";
        $params['date_to'] = $filters['date_to'];
    }

    // Count total records
    $countQuery = "SELECT COUNT(*) as total FROM (" . $query . ") as subquery";
    $countStmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue(':' . $key, $value);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get paginated data
    $query .= " ORDER BY changed_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get statistics
    $statsQuery = "SELECT
        COUNT(*) as total_changes,
        COUNT(CASE WHEN DATE(changed_at) = CURDATE() THEN 1 END) as changes_today,
        COUNT(DISTINCT user_email) as unique_users,
        COUNT(DISTINCT sheet_name) as unique_sheets
        FROM change_logs";
    $statsStmt = $db->query($statsQuery);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    // Group logs by KIT + Client + Date
    $groupedLogs = [];
    $hasSearch = !empty($filters['search']);

    foreach ($logs as $log) {
        $date = date('Y-m-d', strtotime($log['changed_at']));
        $groupKey = ($log['kit_number'] ?? '') . '|' . ($log['client_name'] ?? '') . '|' . $date . '|' . ($log['user_email'] ?? '');

        if ($hasSearch) {
            $groupedLogs[] = ['is_group' => false, 'log' => $log];
        } else {
            if (!isset($groupedLogs[$groupKey])) {
                $groupedLogs[$groupKey] = [
                    'is_group' => true,
                    'count' => 0,
                    'first_time' => $log['changed_at'],
                    'kit_number' => $log['kit_number'],
                    'client_name' => $log['client_name'],
                    'account' => $log['account'],
                    'user_email' => $log['user_email'],
                    'sheet_name' => $log['sheet_name'],
                    'group_key' => $groupKey,
                    'details' => []
                ];
            }
            $groupedLogs[$groupKey]['count']++;
            $groupedLogs[$groupKey]['details'][] = $log;
        }
    }

    // Reindex array
    $groupedLogs = array_values($groupedLogs);

    // Return JSON response
    echo json_encode([
        'success' => true,
        'data' => $groupedLogs,
        'stats' => $stats,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total_records' => $totalRecords,
            'total_pages' => ceil($totalRecords / $perPage)
        ],
        'filters' => $filters,
        'timestamp' => time()
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
