<?php
require_once 'includes/auth_check.php';
require_once 'includes/config.php';
require_once 'includes/functions.php';

$message = '';
$messageType = '';
$canManageActivitySchedule = can_manage_activities();

function has_activity_conflict(PDO $pdo, int $roomId, string $date, string $start, string $end, int $excludeId = 0): bool
{
    $sql = "
        SELECT COUNT(*)
        FROM activities
        WHERE id_sala = ?
          AND data = ?
          AND (
              (ora_start < ? AND ora_end > ?)
              OR (ora_start < ? AND ora_end > ?)
              OR (ora_start >= ? AND ora_end <= ?)
          )
    ";
    $params = [$roomId, $date, $end, $start, $start, $end, $start, $end];

    if ($excludeId > 0) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (int)$stmt->fetchColumn() > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!$canManageActivitySchedule) {
        $message = 'Nu ai permisiunea necesara pentru a modifica activitatile.';
        $messageType = 'alert-error';
    } else {

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM activities WHERE id = ?");

        if ($stmt->execute([$id])) {
            $message = 'Activitatea a fost stearsa.';
            $messageType = 'alert-success';
        }
    }

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $roomId = (int)($_POST['id_sala'] ?? 0);
        $activityName = trim($_POST['nume_activitate'] ?? '');
        $date = trim($_POST['data'] ?? '');
        $start = trim($_POST['ora_start'] ?? '');
        $end = trim($_POST['ora_end'] ?? '');

        if ($end <= $start) {
            $message = 'Eroare: ora de sfarsit trebuie sa fie dupa ora de inceput.';
            $messageType = 'alert-error';
        } elseif (has_activity_conflict($pdo, $roomId, $date, $start, $end, $id)) {
            $message = 'Eroare: exista deja o activitate in aceasta sala in intervalul ales.';
            $messageType = 'alert-error';
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare("
                    UPDATE activities
                    SET id_sala = ?, nume_activitate = ?, data = ?, ora_start = ?, ora_end = ?
                    WHERE id = ?
                ");
                $stmt->execute([$roomId, $activityName, $date, $start, $end, $id]);
                $message = 'Activitatea a fost modificata.';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO activities (id_sala, nume_activitate, data, ora_start, ora_end)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$roomId, $activityName, $date, $start, $end]);
                $message = 'Activitatea a fost planificata.';
            }

            $messageType = 'alert-success';
        }
    }

    if ($action === 'import_csv') {
        $stmt = $pdo->prepare("
            INSERT INTO activities (id, id_sala, nume_activitate, data, ora_start, ora_end)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $existsStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM activities
            WHERE id = ?
               OR (id_sala = ? AND LOWER(nume_activitate) = LOWER(?) AND data = ? AND ora_start = ? AND ora_end = ?)
        ");
        $roomExistsStmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE id = ?");

        $result = import_uploaded_csv('csv_file', function (array $row) use ($pdo, $stmt, $existsStmt, $roomExistsStmt): string {
            $id = isset($row['id']) && is_numeric($row['id']) ? (int)$row['id'] : null;
            $roomId = isset($row['id_sala']) && is_numeric($row['id_sala']) ? (int)$row['id_sala'] : 0;
            $activityName = trim($row['nume_activitate'] ?? '');
            $date = trim($row['data'] ?? '');
            $start = trim($row['ora_start'] ?? '');
            $end = trim($row['ora_end'] ?? '');

            if ($id === null || $id <= 0 || $roomId <= 0 || $activityName === '' || $date === '' || $start === '' || $end === '' || $end <= $start) {
                return 'invalid';
            }

            $roomExistsStmt->execute([$roomId]);

            if ((int)$roomExistsStmt->fetchColumn() === 0) {
                return 'missing_reference';
            }

            $existsStmt->execute([$id, $roomId, $activityName, $date, $start, $end]);

            if ((int)$existsStmt->fetchColumn() > 0 || has_activity_conflict($pdo, $roomId, $date, $start, $end)) {
                return 'skipped';
            }

            $stmt->execute([$id, $roomId, $activityName, $date, $start, $end]);

            return 'imported';
        });

        $message = csv_import_summary('activitati', $result);
        $messageType = $result['error'] ? 'alert-error' : 'alert-success';
    }
    }
}

$rooms = $pdo->query("SELECT * FROM rooms ORDER BY nume ASC")->fetchAll();
$activities = $pdo->query("
    SELECT activities.*, rooms.nume AS nume_sala
    FROM activities
    INNER JOIN rooms ON activities.id_sala = rooms.id
    ORDER BY data DESC, ora_start ASC
")->fetchAll();

$editActivity = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM activities WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editActivity = $stmt->fetch();
}

$pageTitle = 'eSC - Planificare activitati';
require 'includes/header.php';
?>
<section class="page-title">
    <h2>Planificarea antrenamentelor si activitatilor</h2>
    <p>Programeaza activitati pe sali si evita suprapunerile de interval.</p>
</section>

<?php if ($message): ?>
    <div class="<?= e($messageType) ?>"><?= e($message) ?></div>
<?php endif; ?>
<?php if (!$canManageActivitySchedule): ?>
    <div class="alert-success"><?= e(role_read_only_notice('programul activitatilor')) ?></div>
<?php endif; ?>

<?php if ($canManageActivitySchedule): ?>
<section class="form-grid">
    <form action="activities.php" method="POST" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= e($editActivity['id'] ?? '') ?>">

        <h3><?= $editActivity ? 'Editeaza activitate' : 'Programeaza activitate' ?></h3>

        <div class="form-field">
            <label for="activity-room">Sala</label>
            <select id="activity-room" name="id_sala" required>
                <option value="">Alege o sala</option>
                <?php foreach ($rooms as $room): ?>
                    <option value="<?= e($room['id']) ?>"<?= selected_attr($editActivity['id_sala'] ?? '', $room['id']) ?>>
                        <?= e($room['nume']) ?> (capacitate: <?= e($room['capacitate']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field">
            <label for="activity-name">Nume activitate</label>
            <input type="text" id="activity-name" name="nume_activitate" value="<?= e($editActivity['nume_activitate'] ?? '') ?>" required>
        </div>

        <div class="form-field">
            <label for="activity-date">Data</label>
            <input type="date" id="activity-date" name="data" value="<?= e($editActivity['data'] ?? '') ?>" required>
        </div>

        <div class="form-row">
            <div class="form-field">
                <label for="activity-start">Ora start</label>
                <input type="time" id="activity-start" name="ora_start" value="<?= e($editActivity['ora_start'] ?? '') ?>" required>
            </div>
            <div class="form-field">
                <label for="activity-end">Ora sfarsit</label>
                <input type="time" id="activity-end" name="ora_end" value="<?= e($editActivity['ora_end'] ?? '') ?>" required>
            </div>
        </div>

        <button type="submit"><?= $editActivity ? 'Modifica' : 'Programeaza' ?> activitatea</button>
        <?php if ($editActivity): ?>
            <a class="button secondary" href="activities.php">Anuleaza editarea</a>
        <?php endif; ?>
    </form>

    <form action="activities.php" method="POST" enctype="multipart/form-data" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="import_csv">

        <h3>Import activitati CSV</h3>
        <p class="form-note">Format: id, id_sala, nume_activitate, data, ora_start, ora_end</p>

        <div class="form-field">
            <label for="activities-csv-file">Fisier CSV</label>
            <input type="file" id="activities-csv-file" name="csv_file" accept=".csv" required>
        </div>

        <button type="submit">Importa CSV</button>
    </form>
</section>
<?php endif; ?>

<section class="panel">
    <h3>Calendar programari</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Interval orar</th>
                    <th>Activitate</th>
                    <th>Sala</th>
                    <th>Actiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activities as $activity): ?>
                    <tr>
                        <td><?= e($activity['data']) ?></td>
                        <td><?= e(substr($activity['ora_start'], 0, 5)) ?> - <?= e(substr($activity['ora_end'], 0, 5)) ?></td>
                        <td><?= e($activity['nume_activitate']) ?></td>
                        <td><?= e($activity['nume_sala']) ?></td>
                        <td>
                            <?php if ($canManageActivitySchedule): ?>
                                <div class="action-list">
                                    <a class="button compact secondary" href="activities.php?edit=<?= e($activity['id']) ?>">Modifica</a>
                                    <form action="activities.php" method="POST" class="inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= e($activity['id']) ?>">
                                        <button type="submit" class="compact danger">Sterge</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="empty-state">Vizualizare</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$activities): ?>
                    <tr>
                        <td colspan="5" class="centered">Nicio activitate planificata momentan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
