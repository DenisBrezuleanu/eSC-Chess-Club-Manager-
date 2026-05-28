<?php
require_once 'includes/auth_check.php';
require_once 'includes/config.php';
require_once 'includes/functions.php';

$message = '';
$messageType = '';
$canManageRooms = can_manage_admin_data();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!$canManageRooms) {
        $message = 'Nu ai permisiunea necesară pentru a modifica sălile.';
        $messageType = 'alert-error';
    } else {

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");

        if ($stmt->execute([$id])) {
            $message = 'Sala a fost ștearsă.';
            $messageType = 'alert-success';
        }
    }

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $nume = trim($_POST['nume'] ?? '');
        $capacitate = (int)($_POST['capacitate'] ?? 0);
        $dotari = trim($_POST['dotari'] ?? '');

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE rooms SET nume = ?, capacitate = ?, dotari = ? WHERE id = ?");
            $stmt->execute([$nume, $capacitate, $dotari, $id]);
            $message = 'Sala a fost modificată.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO rooms (nume, capacitate, dotari) VALUES (?, ?, ?)");
            $stmt->execute([$nume, $capacitate, $dotari]);
            $message = 'Sala a fost adăugată.';
        }

        $messageType = 'alert-success';
    }

    if ($action === 'import_csv') {
        $stmt = $pdo->prepare("INSERT INTO rooms (id, nume, capacitate, dotari) VALUES (?, ?, ?, ?)");
        $existsStmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE id = ? OR LOWER(nume) = LOWER(?)");

        $result = import_uploaded_csv('csv_file', function (array $row) use ($stmt, $existsStmt): string {
            $id = isset($row['id']) && is_numeric($row['id']) ? (int)$row['id'] : null;
            $nume = trim($row['nume'] ?? '');
            $capacitate = isset($row['capacitate']) ? (int)$row['capacitate'] : 0;
            $dotari = trim($row['dotari'] ?? '');

            if ($id === null || $id <= 0 || $nume === '' || $capacitate <= 0) {
                return 'invalid';
            }

            $existsStmt->execute([$id, $nume]);

            if ((int)$existsStmt->fetchColumn() > 0) {
                return 'skipped';
            }

            $stmt->execute([$id, $nume, $capacitate, $dotari]);

            return 'imported';
        });

        $message = csv_import_summary('săli', $result);
        $messageType = $result['error'] ? 'alert-error' : 'alert-success';
    }
    }
}

$rooms = $pdo->query("SELECT * FROM rooms ORDER BY id DESC")->fetchAll();

$editRoom = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editRoom = $stmt->fetch();
}

$pageTitle = 'eSC - Gestiune săli';
require 'includes/header.php';
?>
<section class="page-title">
    <h2>Gestiunea sălilor</h2>
    <p>Administrează sălile și dotările folosite pentru activități.</p>
</section>

<?php if ($message): ?>
    <div class="<?= e($messageType) ?>"><?= e($message) ?></div>
<?php endif; ?>
<?php if (!$canManageRooms): ?>
    <div class="alert-success"><?= e(role_read_only_notice('săli')) ?></div>
<?php endif; ?>

<?php if ($canManageRooms): ?>
<section class="form-grid">
    <form action="rooms.php" method="POST" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= e($editRoom['id'] ?? '') ?>">

        <h3><?= $editRoom ? 'Editează sală' : 'Adaugă sală' ?></h3>

        <div class="form-field">
            <label for="room-nume">Nume sală</label>
            <input type="text" id="room-nume" name="nume" value="<?= e($editRoom['nume'] ?? '') ?>" required>
        </div>

        <div class="form-field">
            <label for="room-capacitate">Capacitate</label>
            <input type="number" id="room-capacitate" name="capacitate" min="1" value="<?= e($editRoom['capacitate'] ?? '') ?>" required>
        </div>

        <div class="form-field">
            <label for="room-dotari">Dotări</label>
            <textarea id="room-dotari" name="dotari" rows="4" required><?= e($editRoom['dotari'] ?? '') ?></textarea>
        </div>

        <button type="submit"><?= $editRoom ? 'Modifică' : 'Adaugă' ?> sală</button>
        <?php if ($editRoom): ?>
            <a class="button secondary" href="rooms.php">Anulează editarea</a>
        <?php endif; ?>
    </form>

    <form action="rooms.php" method="POST" enctype="multipart/form-data" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="import_csv">

        <h3>Import săli CSV</h3>
        <p class="form-note">Format: id, nume, capacitate, dotari</p>

        <div class="form-field">
            <label for="rooms-csv-file">Fișier CSV</label>
            <input type="file" id="rooms-csv-file" name="csv_file" accept=".csv" required>
        </div>

        <button type="submit">Importă CSV</button>
    </form>
</section>
<?php endif; ?>

<section class="panel">
    <h3>Lista sălilor</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nume</th>
                    <th>Capacitate</th>
                    <th>Dotări</th>
                    <th>Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $room): ?>
                    <tr>
                        <td><?= e($room['id']) ?></td>
                        <td><?= e($room['nume']) ?></td>
                        <td><?= e($room['capacitate']) ?></td>
                        <td><?= e($room['dotari']) ?></td>
                        <td>
                            <?php if ($canManageRooms): ?>
                                <div class="action-list">
                                    <a class="button compact secondary" href="rooms.php?edit=<?= e($room['id']) ?>">Modifică</a>
                                    <form action="rooms.php" method="POST" class="inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= e($room['id']) ?>">
                                        <button type="submit" class="compact danger">Șterge</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="empty-state">Vizualizare</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rooms): ?>
                    <tr>
                        <td colspan="5" class="centered">Nu există săli înregistrate.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
