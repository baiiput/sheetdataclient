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

// Helper function untuk format nomor WA jadi clickable link dengan tombol copy
function formatWALink($waNumber) {
    if (empty($waNumber) || $waNumber === '-') {
        return '-';
    }

    // Clean nomor (hapus non-digit)
    $cleanNumber = preg_replace('/\D/', '', $waNumber);

    if (empty($cleanNumber)) {
        return htmlspecialchars($waNumber);
    }

    // Format untuk copy (62xxx → 0xxx)
    $copyFormat = '0' . substr($cleanNumber, 2); // 628123456789 → 081234567890

    // Return clickable link + copy button
    return '<div class="wa-number-container">
        <a href="https://wa.me/' . htmlspecialchars($cleanNumber) . '" target="_blank" class="wa-link" title="Chat di WhatsApp">
            <span class="wa-icon">💬</span> ' . htmlspecialchars($cleanNumber) . '
        </a>
        <button class="btn-copy" onclick="copyWA(\'' . htmlspecialchars($copyFormat) . '\')" title="Copy nomor (format 0xxx)">
            <span class="copy-icon">📋</span>
        </button>
    </div>';
}

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
                'account' => $log['account'],
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
                    <div class="auto-refresh-controls">
                        <label class="auto-refresh-toggle">
                            <input type="checkbox" id="auto-refresh-toggle" checked>
                            <span class="toggle-label">🔄 Auto Refresh</span>
                        </label>
                        <select id="refresh-interval" class="refresh-interval-select">
                            <option value="30" selected>30s</option>
                            <option value="60">1min</option>
                            <option value="120">2min</option>
                            <option value="300">5min</option>
                        </select>
                        <span id="last-update" class="last-update-time"></span>
                    </div>
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

    <!-- Auto Refresh Progress Bar -->
    <div class="refresh-progress-container">
        <div class="refresh-progress-bar" id="refresh-progress-bar"></div>
    </div>

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
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;" onclick="toggleFilterPanel()">
                    <h2 class="card-title">🔍 Filter & Pencarian</h2>
                    <span id="filter-toggle-icon" style="font-size: 20px; transition: transform 0.3s; transform: rotate(-90deg);">▶</span>
                </div>
                <div class="card-body collapsible collapsed" id="filter-panel">
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
                                        <th>Client</th>
                                        <th>KIT</th>
                                        <th>Account</th>
                                        <th>Nomor WA</th>
                                        <th>Sheet</th>
                                        <th>Perubahan</th>
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
                                            <td><?= htmlspecialchars($group['client_name'] ?? '-') ?></td>
                                            <td class="kit-cell"><?= nl2br(htmlspecialchars($group['kit_number'] ?? '-'), false) ?></td>
                                            <td><?= htmlspecialchars($group['account'] ?? '-') ?></td>
                                            <td><?= formatWALink($group['user_email']) ?></td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    <?= htmlspecialchars($group['sheet_name']) ?>
                                                </span>
                                            </td>
                                            <td class="group-summary">
                                                <strong><?= $group['count'] ?> perubahan</strong>
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
                                                <td colspan="5" class="detail-change">
                                                    <div>
                                                        <span class="text-muted">OLD:</span> <?= htmlspecialchars($log['old_value'] ?? '-') ?>
                                                        <br>
                                                        <span class="text-muted">NEW:</span> <?= htmlspecialchars($log['new_value'] ?? '-') ?>
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
                                                        <span class="badge <?= $badgeClass ?>" style="margin-left: 12px; vertical-align: middle;">
                                                            <?= $badgeIcon ?> <?= htmlspecialchars($actionType) ?>
                                                        </span>
                                                    </div>
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
                                            <td><?= htmlspecialchars($log['client_name'] ?? '-') ?></td>
                                            <td class="kit-cell"><?= nl2br(htmlspecialchars($log['kit_number'] ?? '-'), false) ?></td>
                                            <td><?= htmlspecialchars($log['account'] ?? '-') ?></td>
                                            <td><?= formatWALink($log['user_email']) ?></td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    <?= htmlspecialchars($log['sheet_name']) ?>
                                                </span>
                                            </td>
                                            <td class="change-cell">
                                                <div>
                                                    <span class="change-old"><?= htmlspecialchars($log['old_value'] ?? '-') ?></span>
                                                    <br>
                                                    <span class="change-new"><?= htmlspecialchars($log['new_value'] ?? '-') ?></span>
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
                                                    <span class="badge <?= $badgeClass ?>" style="margin-left: 12px; vertical-align: middle;">
                                                        <?= $badgeIcon ?> <?= htmlspecialchars($actionType) ?>
                                                    </span>
                                                </div>
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
    // Toggle group expand/collapse with smooth animation
    function toggleGroup(groupId) {
        const detailRows = document.querySelectorAll(`tr.group-detail[data-group="${groupId}"]`);
        const headerRow = document.querySelector(`tr.group-header[data-group="${groupId}"]`);
        const icon = document.getElementById(`icon-${groupId}`);
        const isExpanded = headerRow && headerRow.classList.contains('expanded');

        // Toggle header expanded class
        if (headerRow) {
            if (isExpanded) {
                headerRow.classList.remove('expanded');
            } else {
                headerRow.classList.add('expanded');
            }
        }

        // Toggle detail rows with smooth animation
        detailRows.forEach(row => {
            if (isExpanded) {
                // Collapsing
                row.classList.remove('show');
                row.classList.add('hiding');
                // After animation, hide completely
                setTimeout(() => {
                    row.style.display = 'none';
                    row.classList.remove('hiding');
                }, 300); // Match CSS transition duration
            } else {
                // Expanding
                row.style.display = 'table-row';
                // Trigger reflow for animation
                void row.offsetHeight;
                row.classList.add('show');
            }
        });

        // Toggle icon
        if (icon) {
            icon.textContent = isExpanded ? '▶' : '▼';
        }
    }

    // Toggle filter panel show/hide with smooth animation
    function toggleFilterPanel() {
        const panel = document.getElementById('filter-panel');
        const icon = document.getElementById('filter-toggle-icon');

        // Toggle collapsed class
        const isCollapsed = panel.classList.toggle('collapsed');

        if (isCollapsed) {
            // Collapsing
            icon.textContent = '▶';
            icon.style.transform = 'rotate(-90deg)';
        } else {
            // Expanding
            icon.textContent = '▼';
            icon.style.transform = 'rotate(0deg)';
        }
    }

    // Copy WhatsApp number to clipboard (convert from 62xxx to 0xxx format)
    function copyWA(number) {
        if (!number) return;

        // Copy to clipboard
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(number).then(() => {
                // Show success feedback
                showCopyFeedback(event.target);
            }).catch(err => {
                // Fallback for older browsers
                fallbackCopy(number);
            });
        } else {
            // Fallback for older browsers
            fallbackCopy(number);
        }
    }

    // Fallback copy method for older browsers
    function fallbackCopy(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            showCopyFeedback(event.target);
        } catch (err) {
            console.error('Failed to copy:', err);
        }
        document.body.removeChild(textArea);
    }

    // Show visual feedback when copy succeeds
    function showCopyFeedback(button) {
        const btn = button.closest('.btn-copy');
        if (!btn) return;

        // Add copied class
        btn.classList.add('copied');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<span class="copy-icon">✓</span>';

        // Remove after 1.5 seconds
        setTimeout(() => {
            btn.classList.remove('copied');
            btn.innerHTML = originalHTML;
        }, 1500);
    }

    // ========================================
    // AUTO REFRESH FUNCTIONALITY
    // ========================================
    let refreshInterval = null;
    let isRefreshing = false;

    // Get current URL params for filtering
    function getCurrentFilters() {
        const params = new URLSearchParams(window.location.search);
        return {
            search: params.get('search') || '',
            sheet: params.get('sheet') || '',
            user: params.get('user') || '',
            date_from: params.get('date_from') || '',
            date_to: params.get('date_to') || '',
            page: params.get('page') || '1'
        };
    }

    // Update statistics cards
    function updateStats(stats) {
        const statCards = document.querySelectorAll('.stat-card .stat-value');
        if (statCards[0]) statCards[0].textContent = Number(stats.total_changes).toLocaleString();
        if (statCards[1]) statCards[1].textContent = Number(stats.changes_today).toLocaleString();
        if (statCards[2]) statCards[2].textContent = Number(stats.unique_users).toLocaleString();
        if (statCards[3]) statCards[3].textContent = Number(stats.unique_sheets).toLocaleString();
    }

    // Update last refresh time
    function updateLastRefreshTime() {
        const now = new Date();
        const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        document.getElementById('last-update').textContent = `📍 ${timeStr}`;
    }

    // Save currently expanded groups
    function getExpandedGroups() {
        const expanded = [];
        document.querySelectorAll('.group-header.expanded').forEach(header => {
            const groupId = header.getAttribute('data-group');
            if (groupId) expanded.push(groupId);
        });
        return expanded;
    }

    // Restore expanded groups after refresh
    function restoreExpandedGroups(expandedIds) {
        expandedIds.forEach(groupId => {
            const header = document.querySelector(`.group-header[data-group="${groupId}"]`);
            if (header && !header.classList.contains('expanded')) {
                toggleGroup(groupId);
            }
        });
    }

    // Fetch and update data
    async function refreshData() {
        if (isRefreshing) return;
        isRefreshing = true;

        // Add visual indicator
        const toggleLabel = document.querySelector('.toggle-label');
        const originalText = toggleLabel.textContent;
        toggleLabel.textContent = '⏳ Refreshing...';

        try {
            // Save expanded groups
            const expandedGroups = getExpandedGroups();

            // Build API URL with current filters
            const filters = getCurrentFilters();
            const apiUrl = new URL('api/get-changes.php', window.location.origin + window.location.pathname.replace('index.php', ''));
            Object.entries(filters).forEach(([key, value]) => {
                if (value) apiUrl.searchParams.append(key, value);
            });

            console.log('Fetching from:', apiUrl.toString());

            // Fetch data with credentials to include session cookie
            const response = await fetch(apiUrl, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            // Get response text first for debugging
            const responseText = await response.text();
            console.log('Response status:', response.status);
            console.log('Response text:', responseText.substring(0, 200));

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            // Try to parse JSON
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response was:', responseText);
                throw new Error('Invalid JSON response from server');
            }

            console.log('Parsed result:', result);

            if (result.success) {
                // Update stats
                updateStats(result.stats);

                // Update table (preserve scroll position)
                const scrollPos = window.scrollY;
                updateTable(result.data, result.filters.search);
                window.scrollTo(0, scrollPos);

                // Restore expanded groups
                setTimeout(() => {
                    restoreExpandedGroups(expandedGroups);
                }, 100);

                // Update last refresh time
                updateLastRefreshTime();

                // Reset label
                toggleLabel.textContent = originalText;
            } else {
                throw new Error(result.error || 'Unknown error');
            }
        } catch (error) {
            console.error('Auto refresh error:', error);
            toggleLabel.textContent = '❌ Error';
            setTimeout(() => {
                toggleLabel.textContent = originalText;
            }, 2000);
        } finally {
            isRefreshing = false;
        }
    }

    // Update table with new data
    function updateTable(groupedLogs, hasSearch) {
        const tbody = document.querySelector('.table tbody');
        if (!tbody) return;

        tbody.innerHTML = '';

        groupedLogs.forEach(item => {
            if (item.is_group) {
                // Create group rows
                const group = item;
                const groupId = 'group-' + btoa(group.group_key).replace(/=/g, '');
                const firstTime = new Date(group.first_time);

                // Group header row
                const headerRow = createGroupHeader(group, groupId, firstTime);
                tbody.appendChild(headerRow);

                // Group detail rows
                group.details.forEach(log => {
                    const detailRow = createGroupDetail(log, groupId);
                    tbody.appendChild(detailRow);
                });
            } else {
                // Create single row
                const row = createSingleRow(item.log);
                tbody.appendChild(row);
            }
        });
    }

    // Format WA number sebagai link
    function formatWALink(waNumber) {
        if (!waNumber || waNumber === '-' || waNumber === '') {
            return '-';
        }

        // Clean nomor (hapus non-digit)
        const cleanNumber = waNumber.replace(/\D/g, '');

        if (!cleanNumber) {
            return escapeHtml(waNumber);
        }

        // Format untuk copy (62xxx → 0xxx)
        const copyFormat = '0' + cleanNumber.substring(2); // 628123456789 → 081234567890

        return `<div class="wa-number-container">
            <a href="https://wa.me/${cleanNumber}" target="_blank" class="wa-link" title="Chat di WhatsApp">
                <span class="wa-icon">💬</span> ${cleanNumber}
            </a>
            <button class="btn-copy" onclick="copyWA('${copyFormat}')" title="Copy nomor (format 0xxx)">
                <span class="copy-icon">📋</span>
            </button>
        </div>`;
    }

    // Create group header row
    function createGroupHeader(group, groupId, firstTime) {
        const tr = document.createElement('tr');
        tr.className = 'group-header';
        tr.setAttribute('data-group', groupId);
        tr.onclick = () => toggleGroup(groupId);

        const dateStr = firstTime.toLocaleDateString('id-ID', {day: '2-digit', month: '2-digit', year: 'numeric'});
        const timeStr = firstTime.toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit', second: '2-digit'});

        tr.innerHTML = `
            <td><span class="expand-icon" id="icon-${groupId}">▶</span></td>
            <td class="text-nowrap">${dateStr}<br><small class="text-muted">${timeStr}</small></td>
            <td>${escapeHtml(group.client_name || '-')}</td>
            <td class="kit-cell">${escapeHtml(group.kit_number || '-').replace(/\n/g, '<br>')}</td>
            <td>${escapeHtml(group.account || '-')}</td>
            <td>${formatWALink(group.user_email)}</td>
            <td><span class="badge badge-secondary">${escapeHtml(group.sheet_name)}</span></td>
            <td class="group-summary"><strong>${group.count} perubahan</strong></td>
        `;

        return tr;
    }

    // Create group detail row
    function createGroupDetail(log, groupId) {
        const tr = document.createElement('tr');
        tr.className = 'group-detail';
        tr.setAttribute('data-group', groupId);
        tr.style.display = 'none';

        const datetime = new Date(log.changed_at);
        const timeStr = datetime.toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit', second: '2-digit'});

        const actionType = log.action_type || 'UPDATE';
        const badge = getActionBadge(actionType);

        tr.innerHTML = `
            <td></td>
            <td class="text-nowrap"><small class="text-muted">└─ ${timeStr}</small></td>
            <td colspan="5" class="detail-change">
                <div>
                    <span class="text-muted">OLD:</span> ${escapeHtml(log.old_value || '-')}
                    <br>
                    <span class="text-muted">NEW:</span> ${escapeHtml(log.new_value || '-')}
                    <span class="badge ${badge.class}" style="margin-left: 12px; vertical-align: middle;">
                        ${badge.icon} ${actionType}
                    </span>
                </div>
            </td>
        `;

        return tr;
    }

    // Create single row (non-grouped)
    function createSingleRow(log) {
        const tr = document.createElement('tr');

        const datetime = new Date(log.changed_at);
        const dateStr = datetime.toLocaleDateString('id-ID', {day: '2-digit', month: '2-digit', year: 'numeric'});
        const timeStr = datetime.toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit', second: '2-digit'});

        const actionType = log.action_type || 'UPDATE';
        const badge = getActionBadge(actionType);

        tr.innerHTML = `
            <td>${escapeHtml(log.id)}</td>
            <td class="text-nowrap">${dateStr}<br><small class="text-muted">${timeStr}</small></td>
            <td>${escapeHtml(log.client_name || '-')}</td>
            <td class="kit-cell">${escapeHtml(log.kit_number || '-').replace(/\n/g, '<br>')}</td>
            <td>${escapeHtml(log.account || '-')}</td>
            <td>${formatWALink(log.user_email)}</td>
            <td><span class="badge badge-secondary">${escapeHtml(log.sheet_name)}</span></td>
            <td class="change-cell">
                <div>
                    <span class="change-old">${escapeHtml(log.old_value || '-')}</span>
                    <br>
                    <span class="change-new">${escapeHtml(log.new_value || '-')}</span>
                    <span class="badge ${badge.class}" style="margin-left: 12px; vertical-align: middle;">
                        ${badge.icon} ${actionType}
                    </span>
                </div>
            </td>
        `;

        return tr;
    }

    // Get badge for action type
    function getActionBadge(actionType) {
        if (actionType === 'INSERT' || actionType === 'INSERT_ROW') {
            return { class: 'badge-success', icon: '➕' };
        } else if (actionType === 'DELETE' || actionType === 'DELETE_ROW') {
            return { class: 'badge-danger', icon: '🗑️' };
        }
        return { class: 'badge-warning', icon: '✏️' };
    }

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ========================================
    // PROGRESS BAR FUNCTIONALITY
    // ========================================
    let progressBarInterval = null;

    // Start progress bar countdown
    function startProgressBar(duration) {
        const progressBar = document.getElementById('refresh-progress-bar');
        if (!progressBar) return;

        // Reset to full width
        progressBar.style.transition = 'none';
        progressBar.style.width = '100%';

        // Force reflow
        void progressBar.offsetHeight;

        // Animate to 0 width
        progressBar.style.transition = `width ${duration}ms linear`;
        progressBar.style.width = '0%';
    }

    // Reset progress bar
    function resetProgressBar() {
        const progressBar = document.getElementById('refresh-progress-bar');
        if (!progressBar) return;

        progressBar.style.transition = 'none';
        progressBar.style.width = '100%';
        void progressBar.offsetHeight;
    }

    // Stop progress bar
    function stopProgressBar() {
        const progressBar = document.getElementById('refresh-progress-bar');
        if (!progressBar) return;

        progressBar.style.transition = 'none';
        progressBar.style.width = '0%';
    }

    // Initialize auto refresh
    document.addEventListener('DOMContentLoaded', function() {
        const toggleCheckbox = document.getElementById('auto-refresh-toggle');
        const intervalSelect = document.getElementById('refresh-interval');

        // Function to start auto refresh with progress bar
        function startAutoRefresh() {
            const interval = parseInt(intervalSelect.value) * 1000;

            // Clear existing interval
            if (refreshInterval) clearInterval(refreshInterval);

            // Start progress bar
            startProgressBar(interval);

            // Set refresh interval
            refreshInterval = setInterval(() => {
                refreshData();
                startProgressBar(interval); // Restart progress bar after refresh
            }, interval);

            // Immediate first refresh
            refreshData();
        }

        // Toggle auto refresh on/off
        toggleCheckbox.addEventListener('change', function() {
            if (this.checked) {
                startAutoRefresh();
            } else {
                if (refreshInterval) {
                    clearInterval(refreshInterval);
                    refreshInterval = null;
                }
                stopProgressBar();
            }
        });

        // Change refresh interval
        intervalSelect.addEventListener('change', function() {
            if (toggleCheckbox.checked) {
                startAutoRefresh();
            }
        });

        // Initial last update time
        updateLastRefreshTime();

        // Auto-start refresh if checkbox is checked on page load
        if (toggleCheckbox.checked) {
            startAutoRefresh();
        }
    });
    </script>
</body>
</html>
