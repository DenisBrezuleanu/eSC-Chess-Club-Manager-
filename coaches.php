<?php
require_once 'includes/auth_check.php';
require_once 'includes/config.php';
require_once 'includes/functions.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

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
</section>

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
                            <div class="action-list">
                                <a class="button compact secondary" href="coaches.php?edit=<?= e($coach['id']) ?>">Modifica</a>
                                <form action="coaches.php" method="POST" class="inline-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= e($coach['id']) ?>">
                                    <button type="submit" class="compact danger">Sterge</button>
                                </form>
                            </div>
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
