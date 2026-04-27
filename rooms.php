<?php
require_once 'auth_check.php';
require_once 'config.php';

$message = '';
$messageType = '';

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = "Sala a fost stearsa.";
        $messageType = "alert-success";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nume = trim($_POST['nume'] ?? '');
    $capacitate = (int)($_POST['capacitate'] ?? 0);
    $dotari = trim($_POST['dotari'] ?? '');

    if ($id) {
        $stmt = $pdo->prepare("UPDATE rooms SET nume=?, capacitate=?, dotari=? WHERE id=?");
        $stmt->execute([$nume, $capacitate, $dotari, $id]);
        $message = "Sala a fost modificata.";
        $messageType = "alert-success";
    } else {
        $stmt = $pdo->prepare("INSERT INTO rooms (nume, capacitate, dotari) VALUES (?, ?, ?)");
        $stmt->execute([$nume, $capacitate, $dotari]);
        $message = "Sala a fost adaugata.";
        $messageType = "alert-success";
    }
}

$rooms = $pdo->query("SELECT * FROM rooms")->fetchAll(PDO::FETCH_ASSOC);

$editRoom = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editRoom = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eSC - Gestiune Sali</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>eSC - Chess Club Manager</h1>
    </header>
    
    <nav>
        <a href="index.php">Acasa</a>
        <a href="coaches.php">Antrenori</a>
        <a href="rooms.php">Sali</a>
        <a href="activities.php">Activitati / Calendar</a>
        <a href="members.php">Membri</a>
        <a href="logout.php">Logout</a>
    </nav>
    
    <main>
        <h2>Gestiunea Salilor</h2>

        <?php if ($message): ?>
            <div class="<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form action="rooms.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="id" value="<?= htmlspecialchars($editRoom['id'] ?? '') ?>">
            
            <label>Nume Sala:</label>
            <input type="text" name="nume" value="<?= htmlspecialchars($editRoom['nume'] ?? '') ?>" required>
            
            <label>Capacitate:</label>
            <input type="number" name="capacitate" value="<?= htmlspecialchars($editRoom['capacitate'] ?? '') ?>" required>
            
            <label>Dotari:</label>
            <textarea name="dotari" required><?= htmlspecialchars($editRoom['dotari'] ?? '') ?></textarea>
            
            <button type="submit"><?= $editRoom ? 'Modifica' : 'Adauga' ?> Sala</button>
            <?php if ($editRoom): ?>
                <a href="rooms.php" style="text-align: center; display: block; margin-top: 10px;">Anuleaza</a>
            <?php endif; ?>
        </form>

        <h3>Lista Salilor</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nume</th>
                    <th>Capacitate</th>
                    <th>Dotari</th>
                    <th>Actiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $room): ?>
                <tr>
                    <td><?= htmlspecialchars($room['id']) ?></td>
                    <td><?= htmlspecialchars($room['nume']) ?></td>
                    <td><?= htmlspecialchars($room['capacitate']) ?></td>
                    <td><?= htmlspecialchars($room['dotari']) ?></td>
                    <td>
                        <a href="rooms.php?edit=<?= $room['id'] ?>">Modifica</a> |
                        <a href="rooms.php?delete=<?= $room['id'] ?>" onclick="return confirm('Stergi sala?');">Sterge</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

    <footer>
        <p>&copy; 2026 eSC Chess Club Manager.</p>
    </footer>
</body>
</html>