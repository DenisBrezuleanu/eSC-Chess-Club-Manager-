<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/functions.php';

function ensure_login_account(PDO $pdo, string $username, string $password, string $role, ?int $memberId = null, ?int $coachId = null): void
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);

    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->prepare("
            INSERT INTO users (username, password_hash, role, id_membru, id_antrenor)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role, $memberId, $coachId]);
    }
}

function resolve_linked_id(PDO $pdo, string $table, ?int $candidateId): int
{
    if ($candidateId !== null && $candidateId > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . quote_identifier($table) . " WHERE id = ?");
        $stmt->execute([$candidateId]);

        if ((int)$stmt->fetchColumn() > 0) {
            return $candidateId;
        }
    }

    return (int)$pdo->query("SELECT COALESCE(MIN(id), 0) FROM " . quote_identifier($table))->fetchColumn();
}

ensure_login_account($pdo, 'admin', 'admin123', 'admin');
ensure_login_account($pdo, 'antrenor', 'antrenor123', 'coach', null, 1);
ensure_login_account($pdo, 'membru', 'membru123', 'member', 1, null);

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
        $_SESSION['role'] = normalize_user_role($user['role']);
        $_SESSION['member_id'] = $_SESSION['role'] === 'member' ? resolve_linked_id($pdo, 'members', isset($user['id_membru']) ? (int)$user['id_membru'] : null) : 0;
        $_SESSION['coach_id'] = $_SESSION['role'] === 'coach' ? resolve_linked_id($pdo, 'coaches', isset($user['id_antrenor']) ? (int)$user['id_antrenor'] : null) : 0;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        redirect_to('index.php');
    }

    $error = 'Nume de utilizator sau parolă incorecte.';
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
    <a class="skip-link" href="#main-content">Sari la conținut</a>

    <header class="site-header login-header">
        <div>
            <h1>eSC - Autentificare</h1>
            <p>Acces administrare club de șah</p>
        </div>
    </header>

    <main id="main-content" class="page-shell login-shell" tabindex="-1">
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
                <label for="password">Parolă</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>

            <button type="submit">Autentificare</button>
            <p class="form-note">Conturi demo: admin/admin123, antrenor/antrenor123, membru/membru123</p>
        </form>
    </main>
</body>
</html>
