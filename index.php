<?php
// ========================================
// 📊 DASHBOARD - CHANGE TRACKING SYSTEM
// ========================================

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

// Handle export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filters = [
        'sheet_name' => $_GET['sheet'] ?? '',
        'user_email' => $_GET['user'] ?? '',
        'date_from' => $_GET['date_from'] ?? '',
        'date_to' => $_GET['date_to'] ?? '',
        'search' => $_GET['search'] ?? ''
    ];

    $result = getChangeLogs($filters, 1, 10000); // Max 10k records
    exportToCSV($result['logs'], 'change_logs_' . date('Y-m-d_His') . '.csv');
}

// Get filters from query string
$filters = [
    'sheet_name' => $_GET['sheet'] ?? '',
    'user_email' => $_GET['user'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'search' => $_GET['search'] ?? ''
];

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : RECORDS_PER_PAGE;

// Get data
$result = getChangeLogs($filters, $page, $perPage);
$logs = $result['logs'];
$pagination = $result['pagination'];

// Get filter options
$sheetNames = getSheetNames();
$userEmails = getUserEmails();

// Get statistics
$stats = getDashboardStats();

// Get daily activity
$dailyActivity = getDailyActivityData(7);
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
                        👤 <?= e($currentUser['name']) ?>
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
                                    value="<?= e($filters['search']) ?>"
                                >
                            </div>

                            <!-- Sheet Filter -->
                            <div class="form-group">
                                <label for="sheet">Sheet</label>
                                <select id="sheet" name="sheet" class="form-control">
                                    <option value="">Semua Sheet</option>
                                    <?php foreach ($sheetNames as $sheetName): ?>
                                        <option value="<?= e($sheetName) ?>" <?= $filters['sheet_name'] === $sheetName ? 'selected' : '' ?>>
                                            <?= e($sheetName) ?>
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
                                        <option value="<?= e($userEmail) ?>" <?= $filters['user_email'] === $userEmail ? 'selected' : '' ?>>
                                            <?= e($userEmail) ?>
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
                                    value="<?= e($filters['date_from']) ?>"
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
                                    value="<?= e($filters['date_to']) ?>"
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
                                        <th>Cell</th>
                                        <th>Nilai Lama</th>
                                        <th>Nilai Baru</th>
                                        <th>Client</th>
                                        <th>KIT</th>
                                        <th>Tipe</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?= e($log['id']) ?></td>
                                            <td class="text-nowrap">
                                                <div><?= formatDate($log['changed_at'], 'd/m/Y') ?></div>
                                                <small class="text-muted"><?= formatDate($log['changed_at'], 'H:i:s') ?></small>
                                                <small class="text-muted d-block"><?= timeAgo($log['changed_at']) ?></small>
                                            </td>
                                            <td class="text-truncate" title="<?= e($log['user_email']) ?>">
                                                <?= e(truncate($log['user_email'], 25)) ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    <?= e($log['sheet_name']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <code><?= e($log['cell_address']) ?></code>
                                            </td>
                                            <td class="text-truncate" title="<?= e($log['old_value']) ?>">
                                                <?= e(truncate($log['old_value'] ?? '-', 30)) ?>
                                            </td>
                                            <td class="text-truncate" title="<?= e($log['new_value']) ?>">
                                                <?= e(truncate($log['new_value'] ?? '-', 30)) ?>
                                            </td>
                                            <td class="text-truncate" title="<?= e($log['client_name']) ?>">
                                                <?= e(truncate($log['client_name'] ?? '-', 20)) ?>
                                            </td>
                                            <td><?= e($log['kit_number'] ?? '-') ?></td>
                                            <td>
                                                <span class="badge <?= getChangeTypeBadge($log['old_value'], $log['new_value']) ?>">
                                                    <?= getChangeTypeLabel($log['old_value'], $log['new_value']) ?>
                                                </span>
                                            </td>
                                        </tr>
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
</body>
</html>
