<?php
require_once 'config.php';

$message = '';
$messageType = '';

function hasConflict($pdo, $id_sala, $data, $ora_start, $ora_end, $exclude_id = null) {
    $sql = "SELECT COUNT(*) FROM activities 
            WHERE id_sala = ? AND data = ? 
            AND (
                (ora_start < ? AND ora_end > ?) OR 
                (ora_start < ? AND ora_end > ?) OR
                (ora_start >= ? AND ora_end <= ?)
            )";
    
    $params = [$id_sala, $data, $ora_end, $ora_start, $ora_start, $ora_end, $ora_start, $ora_end];
    
    if ($exclude_id) {
        $sql .= " AND id != ?";
        $params[] = $exclude_id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM activities WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = "Activitatea a fost stearsa.";
        $messageType = "alert-success";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = empty($_POST['id']) ? null : $_POST['id'];
    $id_sala = $_POST['id_sala'];
    $nume_activitate = $_POST['nume_activitate'];
    $data = $_POST['data'];
    $ora_start = $_POST['ora_start'];
    $ora_end = $_POST['ora_end'];

    if ($ora_end <= $ora_start) {
         $message = "Eroare: Ora de sfarsit trebuie sa fie dupa ora de inceput.";
         $messageType = "alert-error";
    } else {
        if (hasConflict($pdo, $id_sala, $data, $ora_start, $ora_end, $id)) {
            $message = "Eroare: A fost detectat un conflict de planificare! Exista deja o activitate programata in aceasta sala in intervalul ales.";
            $messageType = "alert-error";
        } else {
            if ($id) {
                $stmt = $pdo->prepare("UPDATE activities SET id_sala=?, nume_activitate=?, data=?, ora_start=?, ora_end=? WHERE id=?");
                $stmt->execute([$id_sala, $nume_activitate, $data, $ora_start, $ora_end, $id]);
                $message = "Activitatea a fost modificata.";
                $messageType = "alert-success";
            } else {
                $stmt = $pdo->prepare("INSERT INTO activities (id_sala, nume_activitate, data, ora_start, ora_end) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$id_sala, $nume_activitate, $data, $ora_start, $ora_end]);
                $message = "Activitatea a fost planificata cu succes.";
                $messageType = "alert-success";
            }
        }
    }
}

$rooms = $pdo->query("SELECT * FROM rooms")->fetchAll(PDO::FETCH_ASSOC);

$activities = $pdo->query("SELECT activities.*, rooms.nume as nume_sala FROM activities JOIN rooms ON activities.id_sala = rooms.id ORDER BY data DESC, ora_start ASC")->fetchAll(PDO::FETCH_ASSOC);

$editActivity = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM activities WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editActivity = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eSC - Planificare Activitati</title>
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
    </nav>
    
    <main>
        <h2>Planificarea Antrenamentelor/Activitatilor</h2>

        <?php if ($message): ?>
            <div class="<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form action="activities.php" method="POST">
            <input type="hidden" name="id" value="<?= $editActivity['id'] ?? '' ?>">
            
            <label>Sala:</label>
            <select name="id_sala" required>
                <option value="">Alege o sala...</option>
                <?php foreach ($rooms as $room): ?>
                    <option value="<?= $room['id'] ?>" <?= (isset($editActivity['id_sala']) && $editActivity['id_sala'] == $room['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($room['nume']) ?> (Capacitate: <?= $room['capacitate'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Nume Activitate (ex: Antrenament, Curs):</label>
            <input type="text" name="nume_activitate" value="<?= $editActivity['nume_activitate'] ?? '' ?>" required>
            
            <label>Data:</label>
            <input type="date" name="data" value="<?= $editActivity['data'] ?? '' ?>" required>
            
            <label>Ora Start:</label>
            <input type="time" name="ora_start" value="<?= $editActivity['ora_start'] ?? '' ?>" required>

            <label>Ora Sfarsit:</label>
            <input type="time" name="ora_end" value="<?= $editActivity['ora_end'] ?? '' ?>" required>
            
            <button type="submit"><?= $editActivity ? 'Modifica' : 'Programeaza' ?> Activitatea</button>
            <?php if ($editActivity): ?>
                <a href="activities.php" style="text-align: center; display: block; margin-top: 10px;">Anuleaza</a>
            <?php endif; ?>
        </form>

        <h3>Calendar Programari</h3>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Interval Orar</th>
                    <th>Nume Activitate</th>
                    <th>Sala</th>
                    <th>Actiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($activities) > 0): ?>
                    <?php foreach ($activities as $act): ?>
                    <tr>
                        <td><?= htmlspecialchars($act['data']) ?></td>
                        <td><?= htmlspecialchars($act['ora_start']) ?> - <?= htmlspecialchars($act['ora_end']) ?></td>
                        <td><?= htmlspecialchars($act['nume_activitate']) ?></td>
                        <td><?= htmlspecialchars($act['nume_sala']) ?></td>
                        <td>
                            <a href="activities.php?edit=<?= $act['id'] ?>">Modifica</a> |
                            <a href="activities.php?delete=<?= $act['id'] ?>" onclick="return confirm('Stergi aceasta activitate?');">Sterge</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center;">Nicio activitate planificata momentan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <footer>
        <p>&copy; 2026 eSC Chess Club Manager.</p>
    </footer>
</body>
</html>