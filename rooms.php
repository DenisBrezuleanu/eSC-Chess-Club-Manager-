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
        $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");

        if ($stmt->execute([$id])) {
            $message = 'Sala a fost stearsa.';
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
            $message = 'Sala a fost modificata.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO rooms (nume, capacitate, dotari) VALUES (?, ?, ?)");
            $stmt->execute([$nume, $capacitate, $dotari]);
            $message = 'Sala a fost adaugata.';
        }

        $messageType = 'alert-success';
    }
}

$rooms = $pdo->query("SELECT * FROM rooms ORDER BY id DESC")->fetchAll();

$editRoom = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editRoom = $stmt->fetch();
}

$pageTitle = 'eSC - Gestiune sali';
require 'includes/header.php';
?>
<section class="page-title">
    <h2>Gestiunea salilor</h2>
    <p>Administreaza salile si dotarile folosite pentru activitati.</p>
</section>

<?php if ($message): ?>
    <div class="<?= e($messageType) ?>"><?= e($message) ?></div>
<?php endif; ?>

<section class="form-grid">
    <form action="rooms.php" method="POST" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= e($editRoom['id'] ?? '') ?>">

        <h3><?= $editRoom ? 'Editeaza sala' : 'Adauga sala' ?></h3>

        <div class="form-field">
            <label for="room-nume">Nume sala</label>
            <input type="text" id="room-nume" name="nume" value="<?= e($editRoom['nume'] ?? '') ?>" required>
        </div>

        <div class="form-field">
            <label for="room-capacitate">Capacitate</label>
            <input type="number" id="room-capacitate" name="capacitate" min="1" value="<?= e($editRoom['capacitate'] ?? '') ?>" required>
        </div>

        <div class="form-field">
            <label for="room-dotari">Dotari</label>
            <textarea id="room-dotari" name="dotari" rows="4" required><?= e($editRoom['dotari'] ?? '') ?></textarea>
        </div>

        <button type="submit"><?= $editRoom ? 'Modifica' : 'Adauga' ?> sala</button>
        <?php if ($editRoom): ?>
            <a class="button secondary" href="rooms.php">Anuleaza editarea</a>
        <?php endif; ?>
    </form>
</section>

<section class="panel">
    <h3>Lista salilor</h3>
    <div class="table-responsive">
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
                        <td><?= e($room['id']) ?></td>
                        <td><?= e($room['nume']) ?></td>
                        <td><?= e($room['capacitate']) ?></td>
                        <td><?= e($room['dotari']) ?></td>
                        <td>
                            <div class="action-list">
                                <a class="button compact secondary" href="rooms.php?edit=<?= e($room['id']) ?>">Modifica</a>
                                <form action="rooms.php" method="POST" class="inline-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= e($room['id']) ?>">
                                    <button type="submit" class="compact danger">Sterge</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rooms): ?>
                    <tr>
                        <td colspan="5" class="centered">Nu exista sali inregistrate.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
