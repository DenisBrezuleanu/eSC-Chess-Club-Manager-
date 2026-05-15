<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/functions.php';

$stmt = $pdo->query("SELECT COUNT(*) FROM users");
if ((int)$stmt->fetchColumn() === 0) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)")
        ->execute(['admin', $hash, 'admin']);
}

if (isset($_SESSION['user_id'])) {
    redirect_to('index.php');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        die("Eroare de securitate. Token CSRF invalid.");
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        redirect_to('index.php');
    }

    $error = 'Nume de utilizator sau parola incorecte.';
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - eSC</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <header class="site-header login-header">
        <div>
            <h1>eSC - Autentificare</h1>
            <p>Acces administrare club de sah</p>
        </div>
    </header>

    <main class="page-shell login-shell">
        <?php if ($error): ?>
            <div class="alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="form-card">
            <?= csrf_field() ?>

            <div class="form-field">
                <label for="username">Utilizator</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>

            <div class="form-field">
                <label for="password">Parola</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>

            <button type="submit">Autentificare</button>
            <p class="form-note">Cont default: admin / admin123</p>
        </form>
    </main>
</body>
</html>
