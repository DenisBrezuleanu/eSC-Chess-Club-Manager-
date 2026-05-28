<?php
require_once 'includes/auth_check.php';
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/competition_helpers.php';

$message = '';
$messageType = '';
$canManageCompetitions = can_manage_admin_data();
$canManageResults = can_manage_competition_results();
$selectedCompetitionId = (int)($_GET['competition_id'] ?? ($_POST['id_competitie'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $adminCompetitionActions = ['save_competition', 'import_competitions_csv', 'delete_competition', 'save_award', 'import_awards_csv', 'import_member_awards_csv'];
    $resultActions = ['add_participant', 'import_participants_csv', 'assign_award'];

    if (in_array($action, $adminCompetitionActions, true) && !$canManageCompetitions) {
        $message = 'Nu ai permisiunea necesară pentru a modifica structura competițiilor sau premiilor.';
        $messageType = 'alert-error';
    } elseif (in_array($action, $resultActions, true) && !$canManageResults) {
        $message = 'Nu ai permisiunea necesară pentru a modifica rezultatele competițiilor.';
        $messageType = 'alert-error';
    } else {

    if ($action === 'save_competition') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['nume'] ?? '');
        $date = trim($_POST['data'] ?? '');
        $location = trim($_POST['locatie'] ?? '');
        $type = trim($_POST['tip'] ?? '');

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE competitions SET nume = ?, data = ?, locatie = ?, tip = ? WHERE id = ?");
            $stmt->execute([$name, $date, $location, $type, $id]);
            $selectedCompetitionId = $id;
            $message = 'Competiția a fost modificată.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO competitions (nume, data, locatie, tip) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $date, $location, $type]);
            $selectedCompetitionId = (int)$pdo->lastInsertId();
            $message = 'Competiția a fost adăugată.';
        }

        $messageType = 'alert-success';
    }

    if ($action === 'import_competitions_csv') {
        $stmt = $pdo->prepare("INSERT INTO competitions (id, nume, data, locatie, tip) VALUES (?, ?, ?, ?, ?)");
        $existsStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM competitions
            WHERE id = ? OR (LOWER(nume) = LOWER(?) AND data = ? AND LOWER(locatie) = LOWER(?))
        ");

        $result = import_uploaded_csv('csv_file', function (array $row) use ($stmt, $existsStmt): string {
            $id = isset($row['id']) && is_numeric($row['id']) ? (int)$row['id'] : null;
            $name = trim($row['nume'] ?? '');
            $date = trim($row['data'] ?? ($row['data_start'] ?? ''));
            $location = trim($row['locatie'] ?? '');
            $type = trim($row['tip'] ?? '');

            if ($id === null || $id <= 0 || $name === '' || $date === '' || $location === '' || $type === '') {
                return 'invalid';
            }

            $existsStmt->execute([$id, $name, $date, $location]);

            if ((int)$existsStmt->fetchColumn() > 0) {
                return 'skipped';
            }

            $stmt->execute([$id, $name, $date, $location, $type]);

            return 'imported';
        });

        $message = csv_import_summary('competiții', $result);
        $messageType = $result['error'] ? 'alert-error' : 'alert-success';
    }

    if ($action === 'delete_competition') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM competitions WHERE id = ?");

        if ($stmt->execute([$id])) {
            $selectedCompetitionId = $selectedCompetitionId === $id ? 0 : $selectedCompetitionId;
            $message = 'Competiția a fost ștearsă.';
            $messageType = 'alert-success';
        }
    }

    if ($action === 'add_participant') {
        $competitionId = (int)($_POST['id_competitie'] ?? 0);
        $memberId = (int)($_POST['id_membru'] ?? 0);
        $score = (float)($_POST['punctaj_obtinut'] ?? 0);

        $stmt = $pdo->prepare("
            INSERT INTO competition_participants (id_competitie, id_membru, punctaj_obtinut)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE punctaj_obtinut = VALUES(punctaj_obtinut)
        ");
        $stmt->execute([$competitionId, $memberId, $score]);

        $selectedCompetitionId = $competitionId;
        $message = 'Participantul a fost adăugat sau actualizat în clasament.';
        $messageType = 'alert-success';
    }

    if ($action === 'import_participants_csv') {
        $stmt = $pdo->prepare("
            INSERT INTO competition_participants (id_competitie, id_membru, punctaj_obtinut)
            VALUES (?, ?, ?)
        ");
        $existsStmt = $pdo->prepare("SELECT COUNT(*) FROM competition_participants WHERE id_competitie = ? AND id_membru = ?");
        $competitionExistsStmt = $pdo->prepare("SELECT COUNT(*) FROM competitions WHERE id = ?");
        $memberExistsStmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE id = ?");

        $result = import_uploaded_csv('csv_file', function (array $row) use ($stmt, $existsStmt, $competitionExistsStmt, $memberExistsStmt): string {
            $competitionId = isset($row['id_competitie']) && is_numeric($row['id_competitie']) ? (int)$row['id_competitie'] : 0;
            $memberId = isset($row['id_membru']) && is_numeric($row['id_membru']) ? (int)$row['id_membru'] : 0;
            $scoreValue = $row['punctaj_obtinut'] ?? ($row['punctaj'] ?? null);
            $score = is_numeric($scoreValue) ? (float)$scoreValue : null;

            if ($competitionId <= 0 || $memberId <= 0 || $score === null) {
                return 'invalid';
            }

            $competitionExistsStmt->execute([$competitionId]);
            $memberExistsStmt->execute([$memberId]);

            if ((int)$competitionExistsStmt->fetchColumn() === 0 || (int)$memberExistsStmt->fetchColumn() === 0) {
                return 'missing_reference';
            }

            $existsStmt->execute([$competitionId, $memberId]);

            if ((int)$existsStmt->fetchColumn() > 0) {
                return 'skipped';
            }

            $stmt->execute([$competitionId, $memberId, $score]);

            return 'imported';
        });

        $message = csv_import_summary('participanți', $result);
        $messageType = $result['error'] ? 'alert-error' : 'alert-success';
    }

    if ($action === 'save_award') {
        $name = trim($_POST['award_nume'] ?? '');
        $description = trim($_POST['descriere'] ?? '');

        if ($name !== '') {
            $stmt = $pdo->prepare("INSERT INTO awards (nume, descriere) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            $message = 'Premiul a fost creat.';
            $messageType = 'alert-success';
        }
    }

    if ($action === 'import_awards_csv') {
        $stmt = $pdo->prepare("INSERT INTO awards (id, nume, descriere) VALUES (?, ?, ?)");
        $existsStmt = $pdo->prepare("SELECT COUNT(*) FROM awards WHERE id = ? OR LOWER(nume) = LOWER(?)");

        $result = import_uploaded_csv('csv_file', function (array $row) use ($stmt, $existsStmt): string {
            $id = isset($row['id']) && is_numeric($row['id']) ? (int)$row['id'] : null;
            $name = trim($row['nume'] ?? '');
            $description = trim($row['descriere'] ?? '');

            if ($id === null || $id <= 0 || $name === '') {
                return 'invalid';
            }

            $existsStmt->execute([$id, $name]);

            if ((int)$existsStmt->fetchColumn() > 0) {
                return 'skipped';
            }

            $stmt->execute([$id, $name, $description]);

            return 'imported';
        });

        $message = csv_import_summary('premii', $result);
        $messageType = $result['error'] ? 'alert-error' : 'alert-success';
    }

    if ($action === 'assign_award') {
        $awardId = (int)($_POST['id_award'] ?? 0);
        $memberId = (int)($_POST['id_membru'] ?? 0);
        $competitionId = empty($_POST['id_competitie']) ? null : (int)$_POST['id_competitie'];
        $awardDate = trim($_POST['data_acordare'] ?? date('Y-m-d'));

        $stmt = $pdo->prepare("
            INSERT INTO member_awards (id_award, id_membru, id_competitie, data_acordare)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$awardId, $memberId, $competitionId, $awardDate]);

        if ($competitionId) {
            $selectedCompetitionId = $competitionId;
        }

        $message = 'Premiul a fost acordat membrului.';
        $messageType = 'alert-success';
    }

    if ($action === 'import_member_awards_csv') {
        $stmt = $pdo->prepare("
            INSERT INTO member_awards (id, id_award, id_membru, id_competitie, data_acordare)
            VALUES (?, ?, ?, ?, ?)
        ");
        $existsStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM member_awards
            WHERE id = ?
               OR (id_award = ? AND id_membru = ? AND ((id_competitie = ?) OR (id_competitie IS NULL AND ? IS NULL)) AND data_acordare = ?)
        ");
        $awardExistsStmt = $pdo->prepare("SELECT COUNT(*) FROM awards WHERE id = ?");
        $memberExistsStmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE id = ?");
        $competitionExistsStmt = $pdo->prepare("SELECT COUNT(*) FROM competitions WHERE id = ?");

        $result = import_uploaded_csv('csv_file', function (array $row) use ($stmt, $existsStmt, $awardExistsStmt, $memberExistsStmt, $competitionExistsStmt): string {
            $id = isset($row['id']) && is_numeric($row['id']) ? (int)$row['id'] : null;
            $awardId = isset($row['id_award']) && is_numeric($row['id_award']) ? (int)$row['id_award'] : 0;
            $memberId = isset($row['id_membru']) && is_numeric($row['id_membru']) ? (int)$row['id_membru'] : 0;
            $competitionId = isset($row['id_competitie']) && is_numeric($row['id_competitie']) ? (int)$row['id_competitie'] : null;
            $date = trim($row['data_acordare'] ?? '');

            if ($id === null || $id <= 0 || $awardId <= 0 || $memberId <= 0 || $date === '') {
                return 'invalid';
            }

            if ($competitionId === 0) {
                $competitionId = null;
            }

            $awardExistsStmt->execute([$awardId]);
            $memberExistsStmt->execute([$memberId]);

            if ((int)$awardExistsStmt->fetchColumn() === 0 || (int)$memberExistsStmt->fetchColumn() === 0) {
                return 'missing_reference';
            }

            if ($competitionId !== null) {
                $competitionExistsStmt->execute([$competitionId]);

                if ((int)$competitionExistsStmt->fetchColumn() === 0) {
                    return 'missing_reference';
                }
            }

            $existsStmt->execute([$id, $awardId, $memberId, $competitionId, $competitionId, $date]);

            if ((int)$existsStmt->fetchColumn() > 0) {
                return 'skipped';
            }

            $stmt->execute([$id, $awardId, $memberId, $competitionId, $date]);

            return 'imported';
        });

        $message = csv_import_summary('premii acordate', $result);
        $messageType = $result['error'] ? 'alert-error' : 'alert-success';
    }
    }
}

$competitions = $pdo->query("SELECT * FROM competitions ORDER BY data DESC, id DESC")->fetchAll();
$members = $pdo->query("SELECT id, nume FROM members ORDER BY nume ASC")->fetchAll();
$awards = $pdo->query("SELECT * FROM awards ORDER BY nume ASC")->fetchAll();

if ($selectedCompetitionId === 0 && $competitions) {
    $selectedCompetitionId = (int)$competitions[0]['id'];
}

$editCompetition = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM competitions WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editCompetition = $stmt->fetch();
}

$selectedCompetition = null;
if ($selectedCompetitionId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM competitions WHERE id = ?");
    $stmt->execute([$selectedCompetitionId]);
    $selectedCompetition = $stmt->fetch();
}

$leaderboard = $selectedCompetitionId > 0 ? get_competition_leaderboard($pdo, $selectedCompetitionId) : [];

$assignedAwards = $pdo->query("
    SELECT member_awards.id, member_awards.data_acordare, awards.nume AS premiu, members.nume AS membru,
           competitions.nume AS competitie
    FROM member_awards
    INNER JOIN awards ON awards.id = member_awards.id_award
    INNER JOIN members ON members.id = member_awards.id_membru
    LEFT JOIN competitions ON competitions.id = member_awards.id_competitie
    ORDER BY member_awards.data_acordare DESC, member_awards.id DESC
    LIMIT 20
")->fetchAll();

$pageTitle = 'eSC - Competiții și premii';
require 'includes/header.php';
?>
<section class="page-title">
    <h2>Competiții, premii și clasamente</h2>
    <p>Administrează competițiile, înscrie participanți și generează clasamente server-side.</p>
</section>

<?php if ($message): ?>
    <div class="<?= e($messageType) ?>"><?= e($message) ?></div>
<?php endif; ?>
<?php if (!$canManageResults && !$canManageCompetitions): ?>
    <div class="alert-success"><?= e(role_read_only_notice('competiții și clasamente')) ?></div>
<?php elseif (!$canManageCompetitions): ?>
    <div class="alert-success">Ești autentificat ca <?= e(current_user_role_label()) ?>. Poți gestiona participanți, punctaje și premii acordate, dar competițiile și premiile se creează de către admin.</div>
<?php endif; ?>

<?php if ($canManageCompetitions || $canManageResults): ?>
<section class="form-grid">
    <?php if ($canManageCompetitions): ?>
    <form action="competitions.php" method="POST" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_competition">
        <input type="hidden" name="id" value="<?= e($editCompetition['id'] ?? '') ?>">

        <h3><?= $editCompetition ? 'Editează competiție' : 'Adaugă competiție' ?></h3>

        <div class="form-field">
            <label for="competition-nume">Nume competiție</label>
            <input type="text" id="competition-nume" name="nume" value="<?= e($editCompetition['nume'] ?? '') ?>" required>
        </div>

        <div class="form-field">
            <label for="competition-data">Data</label>
            <input type="date" id="competition-data" name="data" value="<?= e($editCompetition['data'] ?? '') ?>" required>
        </div>

        <div class="form-field">
            <label for="competition-locatie">Locație</label>
            <input type="text" id="competition-locatie" name="locatie" value="<?= e($editCompetition['locatie'] ?? '') ?>" required>
        </div>

        <div class="form-field">
            <label for="competition-tip">Tip</label>
            <select id="competition-tip" name="tip" required>
                <option value="">Alege tipul competiției</option>
                <?php foreach (['Clasic', 'Rapid', 'Blitz', 'Intern', 'Extern'] as $type): ?>
                    <option value="<?= e($type) ?>"<?= selected_attr($editCompetition['tip'] ?? 'Clasic', $type) ?>>
                        <?= e($type) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit"><?= $editCompetition ? 'Modifică' : 'Adaugă' ?> competiție</button>
        <?php if ($editCompetition): ?>
            <a class="button secondary" href="competitions.php?competition_id=<?= e($editCompetition['id']) ?>">Anulează editarea</a>
        <?php endif; ?>
    </form>
    <?php endif; ?>

    <?php if ($canManageResults): ?>
    <form action="competitions.php" method="POST" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_participant">

        <h3>Adaugă participant</h3>

        <div class="form-field">
            <label for="participant-competition">Competitie</label>
            <select id="participant-competition" name="id_competitie" required>
                <option value="">Alege competiția</option>
                <?php foreach ($competitions as $competition): ?>
                    <option value="<?= e($competition['id']) ?>"<?= selected_attr($selectedCompetitionId, $competition['id']) ?>>
                        <?= e($competition['nume']) ?> - <?= e($competition['data']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field">
            <label for="participant-member">Membru</label>
            <select id="participant-member" name="id_membru" required>
                <option value="">Alege membrul</option>
                <?php foreach ($members as $member): ?>
                    <option value="<?= e($member['id']) ?>"><?= e($member['nume']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field">
            <label for="participant-score">Punctaj obținut</label>
            <input type="number" id="participant-score" name="punctaj_obtinut" min="0" step="0.01" required>
        </div>

        <button type="submit"<?= (!$competitions || !$members) ? ' disabled' : '' ?>>Adaugă participant</button>
        <?php if (!$competitions || !$members): ?>
            <p class="form-note">Ai nevoie de cel puțin o competiție și un membru.</p>
        <?php endif; ?>
    </form>
    <?php endif; ?>

    <?php if ($canManageCompetitions): ?>
    <form action="competitions.php" method="POST" enctype="multipart/form-data" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="import_competitions_csv">

        <h3>Import competiții CSV</h3>
        <p class="form-note">Format: id, nume, data_start, data_end, locatie, tip</p>

        <div class="form-field">
            <label for="competitions-csv-file">Fișier CSV</label>
            <input type="file" id="competitions-csv-file" name="csv_file" accept=".csv" required>
        </div>

        <button type="submit">Importă competiții</button>
    </form>
    <?php endif; ?>

    <?php if ($canManageResults): ?>
    <form action="competitions.php" method="POST" enctype="multipart/form-data" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="import_participants_csv">

        <h3>Import participanți CSV</h3>
        <p class="form-note">Format: id_competitie, id_membru, punctaj</p>

        <div class="form-field">
            <label for="participants-csv-file">Fișier CSV</label>
            <input type="file" id="participants-csv-file" name="csv_file" accept=".csv" required>
        </div>

        <button type="submit">Importă participanți</button>
    </form>
    <?php endif; ?>
</section>
<?php endif; ?>

<section class="content-grid">
    <div class="panel">
        <h3>Competiții</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nume</th>
                        <th>Data</th>
                        <th>Locație</th>
                        <th>Tip</th>
                        <th>Acțiuni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($competitions as $competition): ?>
                        <tr>
                            <td><?= e($competition['nume']) ?></td>
                            <td><?= e($competition['data']) ?></td>
                            <td><?= e($competition['locatie']) ?></td>
                            <td><?= e($competition['tip']) ?></td>
                            <td>
                                <div class="action-list">
                                    <a class="button compact secondary" href="competitions.php?competition_id=<?= e($competition['id']) ?>">Clasament</a>
                                    <a class="button compact secondary" href="leaderboard.php?competition_id=<?= e($competition['id']) ?>">Pagina clasament</a>
                                    <?php if ($canManageCompetitions): ?>
                                        <a class="button compact secondary" href="competitions.php?edit=<?= e($competition['id']) ?>&competition_id=<?= e($competition['id']) ?>">Modifică</a>
                                        <form action="competitions.php" method="POST" class="inline-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete_competition">
                                            <input type="hidden" name="id" value="<?= e($competition['id']) ?>">
                                            <input type="hidden" name="id_competitie" value="<?= e($competition['id']) ?>">
                                            <button type="submit" class="compact danger">Șterge</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$competitions): ?>
                        <tr>
                            <td colspan="5" class="centered">Nu există competiții înregistrate.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <h3>Clasament<?= $selectedCompetition ? ': ' . e($selectedCompetition['nume']) : '' ?></h3>
        <?= render_leaderboard_html($leaderboard) ?>
    </div>
</section>

<?php if ($canManageCompetitions || $canManageResults): ?>
<section class="form-grid">
    <?php if ($canManageCompetitions): ?>
    <form action="competitions.php" method="POST" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_award">

        <h3>Creează premiu</h3>

        <div class="form-field">
            <label for="award-nume">Nume premiu</label>
            <input type="text" id="award-nume" name="award_nume" required>
        </div>

        <div class="form-field">
            <label for="award-descriere">Descriere</label>
            <textarea id="award-descriere" name="descriere" rows="3"></textarea>
        </div>

        <button type="submit">Salvează premiu</button>
    </form>
    <?php endif; ?>

    <?php if ($canManageResults): ?>
    <form action="competitions.php" method="POST" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="assign_award">

        <h3>Acordă premiu</h3>

        <div class="form-field">
            <label for="assign-award">Premiu</label>
            <select id="assign-award" name="id_award" required>
                <option value="">Alege premiul</option>
                <?php foreach ($awards as $award): ?>
                    <option value="<?= e($award['id']) ?>"><?= e($award['nume']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field">
            <label for="assign-member">Membru</label>
            <select id="assign-member" name="id_membru" required>
                <option value="">Alege membrul</option>
                <?php foreach ($members as $member): ?>
                    <option value="<?= e($member['id']) ?>"><?= e($member['nume']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field">
            <label for="assign-competition">Competitie</label>
            <select id="assign-competition" name="id_competitie">
                <option value="">Fără competiție</option>
                <?php foreach ($competitions as $competition): ?>
                    <option value="<?= e($competition['id']) ?>"<?= selected_attr($selectedCompetitionId, $competition['id']) ?>>
                        <?= e($competition['nume']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field">
            <label for="assign-date">Data acordare</label>
            <input type="date" id="assign-date" name="data_acordare" value="<?= e(date('Y-m-d')) ?>" required>
        </div>

        <button type="submit"<?= (!$awards || !$members) ? ' disabled' : '' ?>>Acordă premiu</button>
        <?php if (!$awards || !$members): ?>
            <p class="form-note">Ai nevoie de cel puțin un premiu și un membru.</p>
        <?php endif; ?>
    </form>
    <?php endif; ?>

    <?php if ($canManageCompetitions): ?>
    <form action="competitions.php" method="POST" enctype="multipart/form-data" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="import_awards_csv">

        <h3>Import premii CSV</h3>
        <p class="form-note">Format: id, nume, descriere</p>

        <div class="form-field">
            <label for="awards-csv-file">Fișier CSV</label>
            <input type="file" id="awards-csv-file" name="csv_file" accept=".csv" required>
        </div>

        <button type="submit">Importă premii</button>
    </form>
    <?php endif; ?>

    <?php if ($canManageCompetitions): ?>
    <form action="competitions.php" method="POST" enctype="multipart/form-data" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="import_member_awards_csv">

        <h3>Import premii acordate CSV</h3>
        <p class="form-note">Format: id, id_award, id_membru, id_competitie, data_acordare</p>

        <div class="form-field">
            <label for="member-awards-csv-file">Fișier CSV</label>
            <input type="file" id="member-awards-csv-file" name="csv_file" accept=".csv" required>
        </div>

        <button type="submit">Importă premii acordate</button>
    </form>
    <?php endif; ?>
</section>
<?php endif; ?>

<section class="panel">
    <h3>Premii acordate recent</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Membru</th>
                    <th>Premiu</th>
                    <th>Competitie</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assignedAwards as $award): ?>
                    <tr>
                        <td><?= e($award['data_acordare']) ?></td>
                        <td><?= e($award['membru']) ?></td>
                        <td><?= e($award['premiu']) ?></td>
                        <td><?= e($award['competitie'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$assignedAwards): ?>
                    <tr>
                        <td colspan="4" class="centered">Nu există premii acordate.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
