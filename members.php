<?php
require_once 'includes/auth_check.php';
require_once 'includes/config.php';
require_once 'includes/functions.php';

$message = '';
$messageType = '';
$canManageMembers = can_manage_admin_data();
$isMemberProfileView = is_member();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!$canManageMembers) {
        $message = 'Nu ai permisiunea necesară pentru a modifica membrii.';
        $messageType = 'alert-error';
    } else {

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM members WHERE id = ?");

        if ($stmt->execute([$id])) {
            $message = 'Membrul a fost șters.';
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
            $message = 'Membrul a fost adăugat.';
        }

        $messageType = 'alert-success';
    }

    if ($action === 'import_csv') {
        $stmt = $pdo->prepare("
            INSERT INTO members (nume, tip_membru, nivel_joc, id_antrenor_asociat)
            VALUES (?, ?, ?, ?)
        ");
        $stmtWithId = $pdo->prepare("
            INSERT INTO members (id, nume, tip_membru, nivel_joc, id_antrenor_asociat)
            VALUES (?, ?, ?, ?, ?)
        ");
        $memberExistsStmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE id = ? OR LOWER(nume) = LOWER(?)");
        $coachExistsStmt = $pdo->prepare("SELECT COUNT(*) FROM coaches WHERE id = ?");
        $validTypes = ['junior', 'senior', 'amator', 'pro'];

        $result = import_uploaded_csv('csv_file', function (array $row) use ($stmt, $stmtWithId, $memberExistsStmt, $coachExistsStmt, $validTypes): string {
            $id = isset($row['id']) && is_numeric($row['id']) ? (int)$row['id'] : null;
            $nume = trim($row['nume'] ?? '');
            $tipMembru = strtolower(trim($row['tip_membru'] ?? 'amator'));
            $nivelJoc = trim($row['nivel_joc'] ?? '');
            $coachValue = $row['id_antrenor_asociat'] ?? ($row['id_antrenor'] ?? null);
            $coachId = is_numeric($coachValue) ? (int)$coachValue : null;

            if ($nume === '') {
                return 'invalid';
            }

            $memberExistsStmt->execute([$id ?? 0, $nume]);

            if ((int)$memberExistsStmt->fetchColumn() > 0) {
                return 'skipped';
            }

            if (!in_array($tipMembru, $validTypes, true)) {
                $tipMembru = 'amator';
            }

            if ($coachId === 0) {
                $coachId = null;
            }

            if ($coachId !== null) {
                $coachExistsStmt->execute([$coachId]);

                if ((int)$coachExistsStmt->fetchColumn() === 0) {
                    $coachId = null;
                }
            }

            if ($id !== null && $id > 0) {
                $stmtWithId->execute([$id, $nume, $tipMembru, $nivelJoc, $coachId]);
            } else {
                $stmt->execute([$nume, $tipMembru, $nivelJoc, $coachId]);
            }

            return 'imported';
        });

        $message = csv_import_summary('membri', $result);
        $messageType = $result['error'] ? 'alert-error' : 'alert-success';
    }
    }
}

$coaches = $pdo->query("SELECT * FROM coaches ORDER BY nume ASC")->fetchAll();
if ($isMemberProfileView) {
    $stmt = $pdo->prepare("
        SELECT members.*, coaches.nume AS nume_antrenor
        FROM members
        LEFT JOIN coaches ON members.id_antrenor_asociat = coaches.id
        WHERE members.id = ?
        ORDER BY members.id DESC
    ");
    $stmt->execute([current_member_id()]);
    $members = $stmt->fetchAll();
} else {
    $members = $pdo->query("
        SELECT members.*, coaches.nume AS nume_antrenor
        FROM members
        LEFT JOIN coaches ON members.id_antrenor_asociat = coaches.id
        ORDER BY members.id DESC
    ")->fetchAll();
}

$editMember = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editMember = $stmt->fetch();
}

$pageTitle = $isMemberProfileView ? 'eSC - Profil membru' : 'eSC - Gestiune membri';
require 'includes/header.php';
?>
<section class="page-title">
    <h2><?= $isMemberProfileView ? 'Profilul meu de membru' : 'Gestiunea membrilor clubului' ?></h2>
    <p><?= $isMemberProfileView ? 'Vizualizează datele tale și accesează istoricul competițional.' : 'Administrează membri, tipul de joc și antrenorul asociat.' ?></p>
</section>

<?php if ($message): ?>
    <div class="<?= e($messageType) ?>"><?= e($message) ?></div>
<?php endif; ?>
<?php if (!$canManageMembers): ?>
    <div class="alert-success"><?= e(role_read_only_notice($isMemberProfileView ? 'profilul tău' : 'membri')) ?></div>
<?php endif; ?>

<?php if ($canManageMembers): ?>
<section class="form-grid">
    <form action="members.php" method="POST" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= e($editMember['id'] ?? '') ?>">

        <h3><?= $editMember ? 'Editează membru' : 'Adaugă membru' ?></h3>

        <div class="form-field">
            <label for="member-nume">Nume membru</label>
            <input type="text" id="member-nume" name="nume" value="<?= e($editMember['nume'] ?? '') ?>" required>
        </div>

        <div class="form-field">
            <label for="member-tip">Tip membru</label>
            <select id="member-tip" name="tip_membru" required>
                <option value="">Alege tipul membrului</option>
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
                <option value="">Fără antrenor</option>
                <?php foreach ($coaches as $coach): ?>
                    <option value="<?= e($coach['id']) ?>"<?= selected_attr($editMember['id_antrenor_asociat'] ?? '', $coach['id']) ?>>
                        <?= e($coach['nume']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit"><?= $editMember ? 'Modifică' : 'Adaugă' ?> membru</button>
        <?php if ($editMember): ?>
            <a class="button secondary" href="members.php">Anulează editarea</a>
        <?php endif; ?>
    </form>

    <form action="members.php" method="POST" enctype="multipart/form-data" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="import_csv">

        <h3>Import membri CSV</h3>
        <p class="form-note">Format: id, nume, tip_membru, nivel_joc, id_antrenor_asociat</p>

        <div class="form-field">
            <label for="csv-file">Fișier CSV</label>
            <input type="file" id="csv-file" name="csv_file" accept=".csv" required>
        </div>

        <button type="submit">Importă CSV</button>
    </form>
</section>
<?php endif; ?>

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
                    <th>Acțiuni</th>
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
                                <?php if ($canManageMembers): ?>
                                    <a class="button compact secondary" href="members.php?edit=<?= e($member['id']) ?>">Modifică</a>
                                <?php endif; ?>
                                <a class="button compact secondary" href="performance_history.php?member_id=<?= e($member['id']) ?>">Istoric</a>
                                <?php if ($canManageMembers): ?>
                                    <form action="members.php" method="POST" class="inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= e($member['id']) ?>">
                                        <button type="submit" class="compact danger">Șterge</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$members): ?>
                    <tr>
                        <td colspan="6" class="centered">Nu există membri înregistrați.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
