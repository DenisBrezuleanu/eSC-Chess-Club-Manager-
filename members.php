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
        $stmt = $pdo->prepare("DELETE FROM members WHERE id = ?");

        if ($stmt->execute([$id])) {
            $message = 'Membrul a fost sters.';
            $messageType = 'alert-success';
        }
    }

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $nume = trim($_POST['nume'] ?? '');
        $tipMembru = strtolower(trim($_POST['tip_membru'] ?? 'amator'));
        $nivelJoc = trim($_POST['nivel_joc'] ?? '');
        $coachId = empty($_POST['id_antrenor_asociat']) ? null : (int)$_POST['id_antrenor_asociat'];
        $validTypes = ['junior', 'senior', 'amator', 'pro'];

        if (!in_array($tipMembru, $validTypes, true)) {
            $tipMembru = 'amator';
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("
                UPDATE members
                SET nume = ?, tip_membru = ?, nivel_joc = ?, id_antrenor_asociat = ?
                WHERE id = ?
            ");
            $stmt->execute([$nume, $tipMembru, $nivelJoc, $coachId, $id]);
            $message = 'Datele membrului au fost modificate.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO members (nume, tip_membru, nivel_joc, id_antrenor_asociat)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$nume, $tipMembru, $nivelJoc, $coachId]);
            $message = 'Membrul a fost adaugat.';
        }

        $messageType = 'alert-success';
    }

    if ($action === 'import_csv') {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $message = 'Te rugam sa alegi un fisier CSV valid.';
            $messageType = 'alert-error';
        } else {
            $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
            $importedCount = 0;

            if ($handle === false) {
                $message = 'Eroare la deschiderea fisierului CSV.';
                $messageType = 'alert-error';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO members (nume, tip_membru, nivel_joc, id_antrenor_asociat)
                    VALUES (?, ?, ?, ?)
                ");
                $firstLine = true;
                $validTypes = ['junior', 'senior', 'amator', 'pro'];

                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    if ($firstLine) {
                        $firstLine = false;

                        if (isset($data[0]) && strtolower(trim($data[0])) === 'nume') {
                            continue;
                        }
                    }

                    if (count($data) < 3) {
                        continue;
                    }

                    $nume = trim($data[0]);
                    $tipMembru = strtolower(trim($data[1]));
                    $nivelJoc = trim($data[2]);
                    $coachId = isset($data[3]) && is_numeric($data[3]) ? (int)$data[3] : null;

                    if ($coachId === 0) {
                        $coachId = null;
                    }

                    if (!in_array($tipMembru, $validTypes, true)) {
                        $tipMembru = 'amator';
                    }

                    if ($nume !== '') {
                        $stmt->execute([$nume, $tipMembru, $nivelJoc, $coachId]);
                        $importedCount++;
                    }
                }

                fclose($handle);
                $message = "Au fost importati $importedCount membri din fisierul CSV.";
                $messageType = 'alert-success';
            }
        }
    }
}

$coaches = $pdo->query("SELECT * FROM coaches ORDER BY nume ASC")->fetchAll();
$members = $pdo->query("
    SELECT members.*, coaches.nume AS nume_antrenor
    FROM members
    LEFT JOIN coaches ON members.id_antrenor_asociat = coaches.id
    ORDER BY members.id DESC
")->fetchAll();

$editMember = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editMember = $stmt->fetch();
}

$pageTitle = 'eSC - Gestiune membri';
require 'includes/header.php';
?>
<section class="page-title">
    <h2>Gestiunea membrilor clubului</h2>
    <p>Administreaza membri, tipul de joc si antrenorul asociat.</p>
</section>

<?php if ($message): ?>
    <div class="<?= e($messageType) ?>"><?= e($message) ?></div>
<?php endif; ?>

<section class="form-grid">
    <form action="members.php" method="POST" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= e($editMember['id'] ?? '') ?>">

        <h3><?= $editMember ? 'Editeaza membru' : 'Adauga membru' ?></h3>

        <div class="form-field">
            <label for="member-nume">Nume membru</label>
            <input type="text" id="member-nume" name="nume" value="<?= e($editMember['nume'] ?? '') ?>" required>
        </div>

        <div class="form-field">
            <label for="member-tip">Tip membru</label>
            <select id="member-tip" name="tip_membru" required>
                <?php foreach (['junior', 'senior', 'amator', 'pro'] as $option): ?>
                    <option value="<?= e($option) ?>"<?= selected_attr($editMember['tip_membru'] ?? 'amator', $option) ?>>
                        <?= e(ucfirst($option)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field">
            <label for="member-nivel">Nivel joc</label>
            <input type="text" id="member-nivel" name="nivel_joc" value="<?= e($editMember['nivel_joc'] ?? '') ?>">
        </div>

        <div class="form-field">
            <label for="member-coach">Antrenor asociat</label>
            <select id="member-coach" name="id_antrenor_asociat">
                <option value="">Fara antrenor</option>
                <?php foreach ($coaches as $coach): ?>
                    <option value="<?= e($coach['id']) ?>"<?= selected_attr($editMember['id_antrenor_asociat'] ?? '', $coach['id']) ?>>
                        <?= e($coach['nume']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit"><?= $editMember ? 'Modifica' : 'Adauga' ?> membru</button>
        <?php if ($editMember): ?>
            <a class="button secondary" href="members.php">Anuleaza editarea</a>
        <?php endif; ?>
    </form>

    <form action="members.php" method="POST" enctype="multipart/form-data" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="import_csv">

        <h3>Import membri CSV</h3>
        <p class="form-note">Format: nume, tip_membru, nivel_joc, id_antrenor</p>

        <div class="form-field">
            <label for="csv-file">Fisier CSV</label>
            <input type="file" id="csv-file" name="csv_file" accept=".csv" required>
        </div>

        <button type="submit">Importa CSV</button>
    </form>
</section>

<section class="panel">
    <h3>Lista membrilor</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nume</th>
                    <th>Tip membru</th>
                    <th>Nivel joc</th>
                    <th>Antrenor asociat</th>
                    <th>Actiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td><?= e($member['id']) ?></td>
                        <td><?= e($member['nume']) ?></td>
                        <td><?= e(ucfirst($member['tip_membru'])) ?></td>
                        <td><?= e($member['nivel_joc'] ?: '-') ?></td>
                        <td><?= e($member['nume_antrenor'] ?? 'N/A') ?></td>
                        <td>
                            <div class="action-list">
                                <a class="button compact secondary" href="members.php?edit=<?= e($member['id']) ?>">Modifica</a>
                                <a class="button compact secondary" href="performance_history.php?member_id=<?= e($member['id']) ?>">Istoric</a>
                                <form action="members.php" method="POST" class="inline-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= e($member['id']) ?>">
                                    <button type="submit" class="compact danger">Sterge</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$members): ?>
                    <tr>
                        <td colspan="6" class="centered">Nu exista membri inregistrati.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
