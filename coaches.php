<?php
require_once 'auth_check.php';
require_once 'config.php';

$message = '';
$messageType = '';

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM coaches WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = "Antrenorul a fost sters.";
        $messageType = "alert-success";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nume = trim($_POST['nume'] ?? '');
    $specializare = trim($_POST['specializare'] ?? '');
    $disponibilitate = trim($_POST['disponibilitate'] ?? '');
    $rol = trim($_POST['rol'] ?? '');
    $grupa = trim($_POST['grupa_asignata'] ?? '');

    if ($id) {
        $stmt = $pdo->prepare("UPDATE coaches SET nume=?, specializare=?, disponibilitate=?, rol=?, grupa_asignata=? WHERE id=?");
        $stmt->execute([$nume, $specializare, $disponibilitate, $rol, $grupa, $id]);
        $message = "Datele au fost modificate cu succes.";
        $messageType = "alert-success";
    } else {
        $stmt = $pdo->prepare("INSERT INTO coaches (nume, specializare, disponibilitate, rol, grupa_asignata) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nume, $specializare, $disponibilitate, $rol, $grupa]);
        $message = "Antrenorul a fost adaugat.";
        $messageType = "alert-success";
    }
}

$coaches = $pdo->query("SELECT * FROM coaches")->fetchAll(PDO::FETCH_ASSOC);

$editCoach = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM coaches WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editCoach = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eSC - Gestiune Antrenori</title>
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
        <h2>Gestiunea Antrenorilor si Colaboratorilor</h2>

        <?php if ($message): ?>
            <div class="<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form action="coaches.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="id" value="<?= htmlspecialchars($editCoach['id'] ?? '') ?>">
            
            <label>Nume:</label>
            <input type="text" name="nume" value="<?= htmlspecialchars($editCoach['nume'] ?? '') ?>" required>
            
            <label>Specializare:</label>
            <input type="text" name="specializare" value="<?= htmlspecialchars($editCoach['specializare'] ?? '') ?>" required>
            
            <label>Disponibilitate:</label>
            <input type="text" name="disponibilitate" value="<?= htmlspecialchars($editCoach['disponibilitate'] ?? '') ?>" required>
            
            <label>Rol:</label>
            <input type="text" name="rol" value="<?= htmlspecialchars($editCoach['rol'] ?? '') ?>" required>
            
            <label>Grupa Asignata:</label>
            <input type="text" name="grupa_asignata" value="<?= htmlspecialchars($editCoach['grupa_asignata'] ?? '') ?>" required>
            
            <button type="submit"><?= $editCoach ? 'Modifica' : 'Adauga' ?> Antrenor</button>
            <?php if ($editCoach): ?>
                <a href="coaches.php" style="text-align: center; display: block; margin-top: 10px;">Anuleaza editarea</a>
            <?php endif; ?>
        </form>

        <h3>Lista Antrenorilor</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nume</th>
                    <th>Specializare</th>
                    <th>Disponibilitate</th>
                    <th>Rol</th>
                    <th>Grupa Asignata</th>
                    <th>Actiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coaches as $coach): ?>
                <tr>
                    <td><?= htmlspecialchars($coach['id']) ?></td>
                    <td><?= htmlspecialchars($coach['nume']) ?></td>
                    <td><?= htmlspecialchars($coach['specializare']) ?></td>
                    <td><?= htmlspecialchars($coach['disponibilitate']) ?></td>
                    <td><?= htmlspecialchars($coach['rol']) ?></td>
                    <td><?= htmlspecialchars($coach['grupa_asignata']) ?></td>
                    <td>
                        <a href="coaches.php?edit=<?= $coach['id'] ?>">Modifica</a> |
                        <a href="coaches.php?delete=<?= $coach['id'] ?>" onclick="return confirm('Sigur stergi acest antrenor?');">Sterge</a>
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