<?php
// ========================================
// 🧪 TEST SIMPLE RENDER - NO CSS
// ========================================
// Test rendering data tanpa styling kompleks

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$db = Database::getInstance();
$sql = "SELECT * FROM change_logs ORDER BY id DESC LIMIT 5";
$logs = $db->fetchAll($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Simple Render Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: white;
            color: black;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 2px solid black;
            padding: 10px;
            text-align: left;
        }
        th {
            background: #333;
            color: white;
        }
        tr:nth-child(even) {
            background: #f0f0f0;
        }
        .debug {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>

<h1>🧪 Simple Render Test (No Complex CSS)</h1>

<div class="debug">
    <strong>Total Records:</strong> <?= count($logs) ?><br>
    <strong>Test Time:</strong> <?= date('Y-m-d H:i:s') ?>
</div>

<?php if (empty($logs)): ?>
    <p style="color: red; font-size: 20px;">❌ NO DATA IN DATABASE!</p>
<?php else: ?>

<h2>Raw Data Dump (First Record):</h2>
<pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ccc;">
<?php print_r($logs[0]); ?>
</pre>

<h2>Table Rendering Test:</h2>

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
        </tr>
    </thead>
    <tbody>
        <?php foreach ($logs as $log): ?>
        <tr>
            <td style="background: yellow;"><?= $log['id'] ?></td>
            <td style="background: lightblue;"><?= $log['changed_at'] ?></td>
            <td style="background: lightgreen;"><?= $log['user_email'] ?></td>
            <td style="background: lightcoral;"><?= $log['sheet_name'] ?></td>
            <td style="background: lightyellow;"><?= $log['cell_address'] ?></td>
            <td style="background: lightpink;"><?= $log['old_value'] ?></td>
            <td style="background: lightcyan;"><?= $log['new_value'] ?></td>
            <td style="background: lavender;"><?= $log['client_name'] ?></td>
            <td style="background: peachpuff;"><?= $log['kit_number'] ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h2>Manual Data Print (to verify array access):</h2>
<?php foreach ($logs as $index => $log): ?>
    <div class="debug">
        <strong>Record #<?= ($index + 1) ?>:</strong><br>
        ID: <strong style="color: red; font-size: 18px;"><?= $log['id'] ?></strong><br>
        User: <strong style="color: blue; font-size: 18px;"><?= $log['user_email'] ?></strong><br>
        Sheet: <strong style="color: green; font-size: 18px;"><?= $log['sheet_name'] ?></strong><br>
        Cell: <strong style="color: purple; font-size: 18px;"><?= $log['cell_address'] ?></strong><br>
        Old: <strong style="color: brown; font-size: 18px;"><?= $log['old_value'] ?></strong><br>
        New: <strong style="color: navy; font-size: 18px;"><?= $log['new_value'] ?></strong><br>
        Client: <strong style="color: teal; font-size: 18px;"><?= $log['client_name'] ?></strong><br>
        KIT: <strong style="color: maroon; font-size: 18px;"><?= $log['kit_number'] ?></strong><br>
    </div>
<?php endforeach; ?>

<?php endif; ?>

<hr>
<p><em>If you can see colored data above, the backend is working perfectly!</em></p>
<p><em>If table is empty but "Manual Data Print" shows data, it's a CSS issue.</em></p>
<p><em>If everything is empty, check browser cache or try incognito mode.</em></p>

</body>
</html>
