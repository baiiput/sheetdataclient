<?php
// ========================================
// 📊 DEBUG VERSION OF INDEX.PHP
// ========================================
// Ini adalah versi debug dari index.php dengan error reporting

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!-- DEBUG MODE ACTIVE -->\n";
echo "<!-- Loading files... -->\n";

try {
    require_once 'includes/config.php';
    echo "<!-- Config loaded -->\n";
} catch (Exception $e) {
    die("ERROR loading config: " . $e->getMessage());
}

try {
    require_once 'includes/db.php';
    echo "<!-- DB loaded -->\n";
} catch (Exception $e) {
    die("ERROR loading db: " . $e->getMessage());
}

try {
    require_once 'includes/functions.php';
    echo "<!-- Functions loaded -->\n";
} catch (Exception $e) {
    die("ERROR loading functions: " . $e->getMessage());
}

echo "<!-- Checking login... -->\n";
try {
    requireLogin();
    echo "<!-- Login OK -->\n";
} catch (Exception $e) {
    die("ERROR in requireLogin: " . $e->getMessage());
}

$currentUser = getCurrentUser();
echo "<!-- Current user: " . $currentUser['name'] . " -->\n";

// Handle logout
if (isset($_GET['logout'])) {
    logoutUser();
    header('Location: login.php');
    exit;
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
echo "<!-- Getting change logs... -->\n";
try {
    $result = getChangeLogs($filters, $page, $perPage);
    $logs = $result['logs'];
    $pagination = $result['pagination'];
    echo "<!-- Got " . count($logs) . " logs -->\n";
} catch (Exception $e) {
    die("ERROR in getChangeLogs: " . $e->getMessage() . "<br>Trace: " . $e->getTraceAsString());
}

// Get filter options
echo "<!-- Getting filter options... -->\n";
try {
    $sheetNames = getSheetNames();
    echo "<!-- Got " . count($sheetNames) . " sheet names -->\n";
} catch (Exception $e) {
    die("ERROR in getSheetNames: " . $e->getMessage());
}

try {
    $userEmails = getUserEmails();
    echo "<!-- Got " . count($userEmails) . " user emails -->\n";
} catch (Exception $e) {
    die("ERROR in getUserEmails: " . $e->getMessage());
}

// Get statistics
echo "<!-- Getting stats... -->\n";
try {
    $stats = getDashboardStats();
    echo "<!-- Stats OK -->\n";
} catch (Exception $e) {
    die("ERROR in getDashboardStats: " . $e->getMessage());
}

// Get daily activity
echo "<!-- Getting daily activity... -->\n";
try {
    $dailyActivity = getDailyActivityData(7);
    echo "<!-- Daily activity OK -->\n";
} catch (Exception $e) {
    echo "<!-- WARNING: dailyActivity failed: " . $e->getMessage() . " -->\n";
    $dailyActivity = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Dashboard DEBUG</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #1a202c;
            color: #f7fafc;
            padding: 20px;
        }
        .debug-banner {
            background: #ff0000;
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            color: black;
        }
        th, td {
            border: 2px solid #333;
            padding: 10px;
            text-align: left;
        }
        th {
            background: #4299e1;
            color: white;
        }
        tr:nth-child(even) {
            background: #f0f0f0;
        }
        .stat-card {
            background: #2d3748;
            padding: 20px;
            margin: 10px;
            border-radius: 8px;
            display: inline-block;
        }
    </style>
</head>
<body>

<div class="debug-banner">
    🔍 DEBUG MODE - This is index-debug.php
</div>

<h1>📊 <?= APP_NAME ?> - Dashboard (DEBUG VERSION)</h1>

<div>
    <p>👤 User: <?= e($currentUser['name']) ?> (<?= e($currentUser['email']) ?>)</p>
    <p><a href="?logout=1">🚪 Logout</a></p>
</div>

<hr>

<h2>📈 Statistics</h2>
<div class="stat-card">
    <strong>Total Changes:</strong> <?= number_format($stats['total_changes']) ?>
</div>
<div class="stat-card">
    <strong>Today:</strong> <?= number_format($stats['changes_today']) ?>
</div>
<div class="stat-card">
    <strong>Users:</strong> <?= number_format($stats['unique_users']) ?>
</div>
<div class="stat-card">
    <strong>Sheets:</strong> <?= number_format($stats['unique_sheets']) ?>
</div>

<hr>

<h2>📋 Change Logs (<?= number_format($pagination['total_records']) ?> records)</h2>

<?php if (empty($logs)): ?>
    <p style="background: yellow; color: black; padding: 20px;">
        ⚠️ NO LOGS FOUND! Array is empty.
    </p>
<?php else: ?>
    <p style="background: green; color: white; padding: 10px;">
        ✅ Found <?= count($logs) ?> logs. Now rendering table...
    </p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Waktu</th>
                <th>User</th>
                <th>Sheet</th>
                <th>Cell</th>
                <th>Old Value</th>
                <th>New Value</th>
                <th>Client</th>
                <th>KIT</th>
                <th>Type</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $index => $log): ?>
                <?php
                // Debug each iteration
                echo "<!-- Row " . ($index + 1) . ": ID = " . ($log['id'] ?? 'NULL') . " -->\n";
                ?>
                <tr style="background: <?= $index % 2 == 0 ? '#fff' : '#f0f0f0' ?>;">
                    <td style="background: yellow; color: black;">
                        <?php
                        try {
                            echo e($log['id']);
                        } catch (Exception $e) {
                            echo "ERROR: " . $e->getMessage();
                        }
                        ?>
                    </td>
                    <td style="background: lightblue; color: black;">
                        <?php
                        try {
                            echo formatDate($log['changed_at'], 'd/m/Y') . "<br>";
                            echo "<small>" . formatDate($log['changed_at'], 'H:i:s') . "</small>";
                        } catch (Exception $e) {
                            echo "ERROR: " . $e->getMessage();
                        }
                        ?>
                    </td>
                    <td style="background: lightgreen; color: black;">
                        <?php
                        try {
                            echo e(truncate($log['user_email'], 25));
                        } catch (Exception $e) {
                            echo "ERROR: " . $e->getMessage();
                        }
                        ?>
                    </td>
                    <td style="background: lightcoral; color: black;">
                        <?php
                        try {
                            echo e($log['sheet_name']);
                        } catch (Exception $e) {
                            echo "ERROR: " . $e->getMessage();
                        }
                        ?>
                    </td>
                    <td style="background: lightyellow; color: black;">
                        <?php
                        try {
                            echo e($log['cell_address']);
                        } catch (Exception $e) {
                            echo "ERROR: " . $e->getMessage();
                        }
                        ?>
                    </td>
                    <td style="background: lightpink; color: black;">
                        <?php
                        try {
                            echo e(truncate($log['old_value'] ?? '-', 30));
                        } catch (Exception $e) {
                            echo "ERROR: " . $e->getMessage();
                        }
                        ?>
                    </td>
                    <td style="background: lightcyan; color: black;">
                        <?php
                        try {
                            echo e(truncate($log['new_value'] ?? '-', 30));
                        } catch (Exception $e) {
                            echo "ERROR: " . $e->getMessage();
                        }
                        ?>
                    </td>
                    <td style="background: lavender; color: black;">
                        <?php
                        try {
                            echo e(truncate($log['client_name'] ?? '-', 20));
                        } catch (Exception $e) {
                            echo "ERROR: " . $e->getMessage();
                        }
                        ?>
                    </td>
                    <td style="background: peachpuff; color: black;">
                        <?php
                        try {
                            echo e($log['kit_number'] ?? '-');
                        } catch (Exception $e) {
                            echo "ERROR: " . $e->getMessage();
                        }
                        ?>
                    </td>
                    <td style="background: wheat; color: black;">
                        <?php
                        try {
                            $badge = getChangeTypeBadge($log['old_value'], $log['new_value']);
                            $label = getChangeTypeLabel($log['old_value'], $log['new_value']);
                            echo "<span style='padding: 5px; background: #333; color: white;'>$label</span>";
                        } catch (Exception $e) {
                            echo "ERROR: " . $e->getMessage();
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<hr>
<p><em>Debug version - errors will be shown inline</em></p>

</body>
</html>
