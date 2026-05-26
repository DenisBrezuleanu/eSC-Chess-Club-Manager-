<?php
require_once 'includes/auth_check.php';
require_once 'includes/config.php';
require_once 'includes/functions.php';

$message = '';
$messageType = '';
$canManageCoaches = can_manage_admin_data();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!$canManageCoaches) {
        $message = 'Nu ai permisiunea necesara pentru a modifica antrenorii.';
        $messageType = 'alert-error';
    } else {

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM coaches WHERE id = ?");

        if ($stmt->execute([$id])) {
            $message = 'Antrenorul a fost sters.';
            $messageType = 'alert-success';
        }
    }

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $nume = trim($_POST['nume'] ?? '');
        $specializare = trim($_POST['specializare'] ?? '');
        $disponibilitate = trim($_POST['disponibilitate'] ?? '');
        $rol = trim($_POST['rol'] ?? '');
        $grupa = trim($_POST['grupa_asignata'] ?? '');

        if ($id > 0) {
            $stmt = $pdo->prepare("
                UPDATE coaches
                SET nume = ?, specializare = ?, disponibilitate = ?, rol = ?, grupa_asignata = ?
                WHERE id = ?
            ");
            $stmt->execute([$nume, $specializare, $disponibilitate, $rol, $grupa, $id]);
            $message = 'Datele antrenorului au fost modificate.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO coaches (nume, specializare, disponibilitate, rol, grupa_asignata)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nume, $specializare, $disponibilitate, $rol, $grupa]);
            $message = 'Antrenorul a fost adaugat.';
        }

        $messageType = 'alert-success';
    }

    if ($action === 'import_csv') {
        $stmt = $pdo->prepare("
            INSERT INTO coaches (id, nume, specializare, disponibilitate, rol, grupa_asignata)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $existsStmt = $pdo->prepare("SELECT COUNT(*) FROM coaches WHERE id = ? OR LOWER(nume) = LOWER(?)");

        $result = import_uploaded_csv('csv_file', function (array $row) use ($stmt, $existsStmt): string {
            $id = isset($row['id']) && is_numeric($row['id']) ? (int)$row['id'] : null;
            $nume = trim($row['nume'] ?? '');
            $specializare = trim($row['specializare'] ?? '');
            $disponibilitate = trim($row['disponibilitate'] ?? '');
            $rol = trim($row['rol'] ?? '');
            $grupa = trim($row['grupa_asignata'] ?? '');

            if ($id === null || $id <= 0 || $nume === '') {
                return 'invalid';
            }

            $existsStmt->execute([$id, $nume]);

            if ((int)$existsStmt->fetchColumn() > 0) {
                return 'skipped';
            }

            $stmt->execute([$id, $nume, $specializare, $disponibilitate, $rol, $grupa]);

            return 'imported';
        });

        $message = csv_import_summary('antrenori', $result);
        $messageType = $result['error'] ? 'alert-error' : 'alert-success';
    }
    }
}

$coaches = $pdo->query("SELECT * FROM coaches ORDER BY id DESC")->fetchAll();

$editCoach = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM coaches WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editCoach = $stmt->fetch();
}

$pageTitle = 'eSC - Gestiune antrenori';
require 'includes/header.php';
?>
<section class="page-title">
    <h2>Gestiunea antrenorilor si colaboratorilor</h2>
    <p>Adauga, modifica si urmareste rolurile antrenorilor din club.</p>
</section>

<?php if ($message): ?>
    <div class="<?= e($messageType) ?>"><?= e($message) ?></div>
<?php endif; ?>
<?php if (!$canManageCoaches): ?>
    <div class="alert-success"><?= e(role_read_only_notice('antrenori')) ?></div>
<?php endif; ?>

<?php if ($canManageCoaches): ?>
<section class="form-grid">
    <form action="coaches.php" method="POST" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= e($editCoach['id'] ?? '') ?>">

        <h3><?= $editCoach ? 'Editeaza antrenor' : 'Adauga antrenor' ?></h3>

        <div class="form-field">
            <label for="coach-nume">Nume</label>
            <input type="text" id="coach-nume" name="nume" value="<?= e($editCoach['nume'] ?? '') ?>" required>
        </div>

        <div class="form-field">
            <label for="coach-specializare">Specializare</label>
            <input type="text" id="coach-specializare" name="specializare" value="<?= e($editCoach['specializare'] ?? '') ?>" required>
        </div>

        <div class="form-field">
            <label for="coach-disponibilitate">Disponibilitate</label>
            <input type="text" id="coach-disponibilitate" name="disponibilitate" value="<?= e($editCoach['disponibilitate'] ?? '') ?>" required>
        </div>

        <div class="form-field">
            <label for="coach-rol">Rol</label>
            <input type="text" id="coach-rol" name="rol" value="<?= e($editCoach['rol'] ?? '') ?>" required>
        </div>

        <div class="form-field">
            <label for="coach-grupa">Grupa asignata</label>
            <input type="text" id="coach-grupa" name="grupa_asignata" value="<?= e($editCoach['grupa_asignata'] ?? '') ?>" required>
        </div>

        <button type="submit"><?= $editCoach ? 'Modifica' : 'Adauga' ?> antrenor</button>
        <?php if ($editCoach): ?>
            <a class="button secondary" href="coaches.php">Anuleaza editarea</a>
        <?php endif; ?>
    </form>

    <form action="coaches.php" method="POST" enctype="multipart/form-data" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="import_csv">

        <h3>Import antrenori CSV</h3>
        <p class="form-note">Format: id, nume, specializare, disponibilitate, rol, grupa_asignata</p>

        <div class="form-field">
            <label for="coaches-csv-file">Fisier CSV</label>
            <input type="file" id="coaches-csv-file" name="csv_file" accept=".csv" required>
        </div>

        <button type="submit">Importa CSV</button>
    </form>
</section>
<?php endif; ?>

<section class="panel">
    <h3>Lista antrenorilor</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nume</th>
                    <th>Specializare</th>
                    <th>Disponibilitate</th>
                    <th>Rol</th>
                    <th>Grupa asignata</th>
                    <th>Actiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coaches as $coach): ?>
                    <tr>
                        <td><?= e($coach['id']) ?></td>
                        <td><?= e($coach['nume']) ?></td>
                        <td><?= e($coach['specializare']) ?></td>
                        <td><?= e($coach['disponibilitate']) ?></td>
                        <td><?= e($coach['rol']) ?></td>
                        <td><?= e($coach['grupa_asignata']) ?></td>
                        <td>
                            <?php if ($canManageCoaches): ?>
                                <div class="action-list">
                                    <a class="button compact secondary" href="coaches.php?edit=<?= e($coach['id']) ?>">Modifica</a>
                                    <form action="coaches.php" method="POST" class="inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= e($coach['id']) ?>">
                                        <button type="submit" class="compact danger">Sterge</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="empty-state">Vizualizare</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$coaches): ?>
                    <tr>
                        <td colspan="7" class="centered">Nu exista antrenori inregistrati.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
