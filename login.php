<?php
// ========================================
// 🔐 LOGIN PAGE
// ========================================

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

startSession();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        $result = loginUser($username, $password);
        if ($result['success']) {
            header('Location: index.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page dark-theme">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>🔐 <?= APP_NAME ?></h1>
                <p>Monitoring Perubahan Google Sheet</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span class="alert-icon">⚠️</span>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span class="alert-icon">✅</span>
                    <span><?= e($success) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="username">
                        <span class="label-icon">👤</span>
                        Username
                    </label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        placeholder="Masukkan username"
                        required
                        autofocus
                        autocomplete="username"
                    >
                </div>

                <div class="form-group">
                    <label for="password">
                        <span class="label-icon">🔑</span>
                        Password
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Masukkan password"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <span class="btn-icon">🚀</span>
                    Login
                </button>
            </form>

            <div class="login-footer">
                <p class="text-muted">
                    Default login: <code>admin</code> / <code>admin123</code>
                    <br>
                    <small>⚠️ Ganti password setelah login pertama!</small>
                </p>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>
