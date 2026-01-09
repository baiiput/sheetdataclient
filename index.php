<?php
// ========================================
// 📊 DASHBOARD - FIXED VERSION
// ========================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

requireLogin();
$currentUser = getCurrentUser();

// Handle logout
if (isset($_GET['logout'])) {
    logoutUser();
    header('Location: login.php');
    exit;
}

// Get filters with default to current month
$defaultDateFrom = date('Y-m-01'); // First day of current month
$defaultDateTo = date('Y-m-t');    // Last day of current month

$filters = [
    'sheet_name' => $_GET['sheet'] ?? '',
    'user_email' => $_GET['user'] ?? '',
    'date_from' => $_GET['date_from'] ?? $defaultDateFrom,
    'date_to' => $_GET['date_to'] ?? $defaultDateTo,
    'search' => $_GET['search'] ?? ''
];

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : RECORDS_PER_PAGE;

// Get data
$result = getChangeLogs($filters, $page, $perPage);
$logs = $result['logs'];
$pagination = $result['pagination'];

// Group logs by KIT + Client + Date for collapsible view
$groupedLogs = [];
$hasSearch = !empty($filters['search']);

foreach ($logs as $log) {
    // Create group key: kit_number|client_name|date
    $date = date('Y-m-d', strtotime($log['changed_at']));
    $groupKey = ($log['kit_number'] ?? '') . '|' . ($log['client_name'] ?? '') . '|' . $date . '|' . ($log['user_email'] ?? '');

    // Skip grouping if search is active (show all results ungrouped for better search UX)
    if ($hasSearch) {
        // When searching, don't group - show all results flat
        $groupedLogs[] = [
            'is_group' => false,
            'log' => $log
        ];
    } else {
        // Normal mode: group by kit+client+date
        if (!isset($groupedLogs[$groupKey])) {
            $groupedLogs[$groupKey] = [
                'is_group' => true,
                'group_key' => $groupKey,
                'kit_number' => $log['kit_number'],
                'client_name' => $log['client_name'],
                'sheet_name' => $log['sheet_name'],
                'user_email' => $log['user_email'],
                'date' => $date,
                'first_time' => $log['changed_at'],
                'last_time' => $log['changed_at'],
                'count' => 0,
                'details' => []
            ];
        }

        $groupedLogs[$groupKey]['count']++;
        $groupedLogs[$groupKey]['details'][] = $log;

        // Update last_time if this change is later
        if (strtotime($log['changed_at']) > strtotime($groupedLogs[$groupKey]['last_time'])) {
            $groupedLogs[$groupKey]['last_time'] = $log['changed_at'];
        }
    }
}

// Convert grouped logs to array (for search results which are not grouped)
if (!$hasSearch) {
    $groupedLogs = array_values($groupedLogs);
}

// Get filter options
$sheetNames = getSheetNames();
$userEmails = getUserEmails();

// Get statistics
$stats = getDashboardStats();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dark-theme">

    <!-- Header -->
    <header class="header">
        <div class="container-fluid">
            <div class="header-content">
                <div class="header-left">
                    <h1 class="header-title">
                        📊 <?= APP_NAME ?>
                    </h1>
                </div>
                <div class="header-right">
                    <span class="user-info">
                        👤 <?= htmlspecialchars($currentUser['name'] ?? 'User') ?>
                    </span>
                    <a href="?logout=1" class="btn btn-sm btn-danger">
                        🚪 Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid">

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📈</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= number_format($stats['total_changes']) ?></div>
                        <div class="stat-label">Total Perubahan</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">🔥</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= number_format($stats['changes_today']) ?></div>
                        <div class="stat-label">Perubahan Hari Ini</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= number_format($stats['unique_users']) ?></div>
                        <div class="stat-label">Total User</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">📋</div>
                    <div class="stat-content">
                        <div class="stat-value"><?= number_format($stats['unique_sheets']) ?></div>
                        <div class="stat-label">Total Sheet</div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">🔍 Filter & Pencarian</h2>
                </div>
                <div class="card-body">
                    <!-- Quick Filters -->
                    <div class="quick-filters">
                        <span class="quick-filter-label">⚡ Quick Filter:</span>
                        <a href="?date_from=<?= date('Y-m-d') ?>&date_to=<?= date('Y-m-d') ?>" class="btn btn-sm btn-quick <?= ($filters['date_from'] === date('Y-m-d') && $filters['date_to'] === date('Y-m-d')) ? 'active' : '' ?>">
                            📅 Hari Ini
                        </a>
                        <a href="?date_from=<?= date('Y-m-d', strtotime('-1 day')) ?>&date_to=<?= date('Y-m-d', strtotime('-1 day')) ?>" class="btn btn-sm btn-quick">
                            🕐 Kemarin
                        </a>
                        <a href="?date_from=<?= date('Y-m-d', strtotime('-7 days')) ?>&date_to=<?= date('Y-m-d') ?>" class="btn btn-sm btn-quick">
                            📊 7 Hari
                        </a>
                        <a href="?date_from=<?= date('Y-m-d', strtotime('-30 days')) ?>&date_to=<?= date('Y-m-d') ?>" class="btn btn-sm btn-quick">
                            📈 30 Hari
                        </a>
                        <a href="?date_from=<?= date('Y-m-01') ?>&date_to=<?= date('Y-m-t') ?>" class="btn btn-sm btn-quick <?= ($filters['date_from'] === date('Y-m-01') && $filters['date_to'] === date('Y-m-t') && empty($filters['search'])) ? 'active' : '' ?>">
                            📆 Bulan Ini
                        </a>
                        <a href="?" class="btn btn-sm btn-secondary">
                            🔄 Reset
                        </a>
                    </div>

                    <!-- Main Filter Form -->
                    <form method="GET" action="" class="filter-form">
                        <div class="filter-grid">
                            <!-- Search -->
                            <div class="form-group">
                                <label for="search">Cari (Nama/KIT/Cell)</label>
                                <input
                                    type="text"
                                    id="search"
                                    name="search"
                                    class="form-control"
                                    placeholder="Cari nama client, KIT number, atau cell..."
                                    value="<?= htmlspecialchars($filters['search']) ?>"
                                >
                            </div>

                            <!-- Sheet Filter -->
                            <div class="form-group">
                                <label for="sheet">Sheet</label>
                                <select id="sheet" name="sheet" class="form-control">
                                    <option value="">Semua Sheet</option>
                                    <?php foreach ($sheetNames as $sheetName): ?>
                                        <option value="<?= htmlspecialchars($sheetName) ?>" <?= $filters['sheet_name'] === $sheetName ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($sheetName) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- User Filter -->
                            <div class="form-group">
                                <label for="user">User</label>
                                <select id="user" name="user" class="form-control">
                                    <option value="">Semua User</option>
                                    <?php foreach ($userEmails as $userEmail): ?>
                                        <option value="<?= htmlspecialchars($userEmail) ?>" <?= $filters['user_email'] === $userEmail ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($userEmail) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Date From -->
                            <div class="form-group">
                                <label for="date_from">Dari Tanggal</label>
                                <input
                                    type="date"
                                    id="date_from"
                                    name="date_from"
                                    class="form-control"
                                    value="<?= htmlspecialchars($filters['date_from']) ?>"
                                >
                            </div>

                            <!-- Date To -->
                            <div class="form-group">
                                <label for="date_to">Sampai Tanggal</label>
                                <input
                                    type="date"
                                    id="date_to"
                                    name="date_to"
                                    class="form-control"
                                    value="<?= htmlspecialchars($filters['date_to']) ?>"
                                >
                            </div>

                            <!-- Per Page -->
                            <div class="form-group">
                                <label for="per_page">Tampilkan</label>
                                <select id="per_page" name="per_page" class="form-control">
                                    <?php foreach (PAGE_SIZE_OPTIONS as $size): ?>
                                        <option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>>
                                            <?= $size ?> data
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">
                                🔍 Cari
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                🔄 Reset
                            </a>
                            <a href="?export=csv&<?= http_build_query($filters) ?>" class="btn btn-success">
                                📥 Export CSV
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Results -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        📋 History Perubahan
                        <span class="badge badge-info">
                            <?= number_format($pagination['total_records']) ?> record
                        </span>
                    </h2>
                </div>
                <div class="card-body">
                    <?php if (empty($logs)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📭</div>
                            <h3>Tidak ada data</h3>
                            <p>Tidak ada perubahan yang sesuai dengan filter Anda.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Waktu</th>
                                        <th>User</th>
                                        <th>Sheet</th>
                                        <th>Nilai Lama</th>
                                        <th>Nilai Baru</th>
                                        <th>Client</th>
                                        <th>KIT</th>
                                        <th>Tipe</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($groupedLogs as $item):
                                        if ($item['is_group']):
                                            // Grouped view - show collapsed row with counter
                                            $group = $item;
                                            $firstTime = new DateTime($group['first_time']);
                                            $groupId = 'group-' . md5($group['group_key']);
                                    ?>
                                        <!-- Group Header Row (Collapsed) -->
                                        <tr class="group-header" data-group="<?= $groupId ?>" onclick="toggleGroup('<?= $groupId ?>')">
                                            <td>
                                                <span class="expand-icon" id="icon-<?= $groupId ?>">▶</span>
                                            </td>
                                            <td class="text-nowrap">
                                                <?= $firstTime->format('d/m/Y') ?><br>
                                                <small class="text-muted"><?= $firstTime->format('H:i:s') ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($group['user_email']) ?></td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    <?= htmlspecialchars($group['sheet_name']) ?>
                                                </span>
                                            </td>
                                            <td colspan="2" class="group-summary">
                                                <strong><?= $group['count'] ?> perubahan</strong>
                                                <span class="text-muted">- Klik untuk expand</span>
                                            </td>
                                            <td><?= htmlspecialchars($group['client_name'] ?? '-') ?></td>
                                            <td class="kit-cell"><?= nl2br(htmlspecialchars($group['kit_number'] ?? '-'), false) ?></td>
                                            <td>
                                                <span class="badge badge-info">GROUP</span>
                                            </td>
                                        </tr>

                                        <!-- Group Detail Rows (Hidden by default) -->
                                        <?php foreach ($group['details'] as $log): ?>
                                            <tr class="group-detail" data-group="<?= $groupId ?>" style="display: none;">
                                                <td></td>
                                                <td class="text-nowrap">
                                                    <?php
                                                    $datetime = new DateTime($log['changed_at']);
                                                    echo '<small class="text-muted">';
                                                    echo '└─ ' . $datetime->format('H:i:s');
                                                    echo '</small>';
                                                    ?>
                                                </td>
                                                <td colspan="3" class="detail-change">
                                                    <span class="text-muted">Old:</span> <?= htmlspecialchars($log['old_value'] ?? '-') ?>
                                                    <br>
                                                    <span class="text-muted">New:</span> <?= htmlspecialchars($log['new_value'] ?? '-') ?>
                                                </td>
                                                <td colspan="2"></td>
                                                <td>
                                                    <?php
                                                    $actionType = $log['action_type'] ?? 'UPDATE';
                                                    $badgeClass = 'badge-warning';
                                                    $badgeIcon = '✏️';

                                                    if ($actionType === 'INSERT' || $actionType === 'INSERT_ROW') {
                                                        $badgeClass = 'badge-success';
                                                        $badgeIcon = '➕';
                                                    } elseif ($actionType === 'DELETE' || $actionType === 'DELETE_ROW') {
                                                        $badgeClass = 'badge-danger';
                                                        $badgeIcon = '🗑️';
                                                    }
                                                    ?>
                                                    <span class="badge <?= $badgeClass ?>">
                                                        <?= $badgeIcon ?> <?= htmlspecialchars($actionType) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>

                                    <?php else:
                                        // Non-grouped view (search results) - show normal row
                                        $log = $item['log'];
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($log['id']) ?></td>
                                            <td class="text-nowrap">
                                                <?php
                                                $datetime = new DateTime($log['changed_at']);
                                                echo $datetime->format('d/m/Y');
                                                echo '<br><small class="text-muted">';
                                                echo $datetime->format('H:i:s');
                                                echo '</small>';
                                                ?>
                                            </td>
                                            <td><?= htmlspecialchars($log['user_email']) ?></td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    <?= htmlspecialchars($log['sheet_name']) ?>
                                                </span>
                                            </td>
                                            <td class="truncate-text"><?= htmlspecialchars($log['old_value'] ?? '-') ?></td>
                                            <td class="truncate-text"><?= htmlspecialchars($log['new_value'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($log['client_name'] ?? '-') ?></td>
                                            <td class="kit-cell"><?= nl2br(htmlspecialchars($log['kit_number'] ?? '-'), false) ?></td>
                                            <td>
                                                <?php
                                                $actionType = $log['action_type'] ?? 'UPDATE';
                                                $badgeClass = 'badge-warning';
                                                $badgeIcon = '✏️';

                                                if ($actionType === 'INSERT' || $actionType === 'INSERT_ROW') {
                                                    $badgeClass = 'badge-success';
                                                    $badgeIcon = '➕';
                                                } elseif ($actionType === 'DELETE' || $actionType === 'DELETE_ROW') {
                                                    $badgeClass = 'badge-danger';
                                                    $badgeIcon = '🗑️';
                                                }
                                                ?>
                                                <span class="badge <?= $badgeClass ?>">
                                                    <?= $badgeIcon ?> <?= htmlspecialchars($actionType) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($pagination['total_pages'] > 1): ?>
                            <div class="pagination">
                                <div class="pagination-info">
                                    Menampilkan <?= number_format(($pagination['current_page'] - 1) * $pagination['per_page'] + 1) ?>
                                    - <?= number_format(min($pagination['current_page'] * $pagination['per_page'], $pagination['total_records'])) ?>
                                    dari <?= number_format($pagination['total_records']) ?> record
                                </div>
                                <div class="pagination-links">
                                    <?php
                                    $queryParams = $_GET;
                                    unset($queryParams['page']);
                                    $baseUrl = 'index.php?' . http_build_query($queryParams) . '&page=';
                                    ?>

                                    <?php if ($pagination['has_prev']): ?>
                                        <a href="<?= $baseUrl . 1 ?>" class="btn btn-sm btn-secondary">⏮️ First</a>
                                        <a href="<?= $baseUrl . ($pagination['current_page'] - 1) ?>" class="btn btn-sm btn-secondary">◀️ Prev</a>
                                    <?php endif; ?>

                                    <span class="current-page">
                                        Halaman <?= $pagination['current_page'] ?> dari <?= $pagination['total_pages'] ?>
                                    </span>

                                    <?php if ($pagination['has_next']): ?>
                                        <a href="<?= $baseUrl . ($pagination['current_page'] + 1) ?>" class="btn btn-sm btn-secondary">Next ▶️</a>
                                        <a href="<?= $baseUrl . $pagination['total_pages'] ?>" class="btn btn-sm btn-secondary">Last ⏭️</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container-fluid">
            <div class="footer-content">
                <div>
                    &copy; <?= date('Y') ?> <?= APP_NAME ?> v<?= APP_VERSION ?>
                </div>
                <div>
                    <button id="theme-toggle" class="btn btn-sm btn-secondary">
                        🌙 Toggle Theme
                    </button>
                </div>
            </div>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
    <script>
    // Toggle group expand/collapse
    function toggleGroup(groupId) {
        const detailRows = document.querySelectorAll(`tr.group-detail[data-group="${groupId}"]`);
        const headerRow = document.querySelector(`tr.group-header[data-group="${groupId}"]`);
        const icon = document.getElementById(`icon-${groupId}`);
        const isExpanded = detailRows[0] && detailRows[0].style.display !== 'none';

        // Toggle detail rows visibility
        detailRows.forEach(row => {
            row.style.display = isExpanded ? 'none' : 'table-row';
        });

        // Toggle header row expanded class
        if (headerRow) {
            if (isExpanded) {
                headerRow.classList.remove('expanded');
            } else {
                headerRow.classList.add('expanded');
            }
        }

        // Toggle icon
        if (icon) {
            icon.textContent = isExpanded ? '▶' : '▼';
        }
    }
    </script>
</body>
</html>
