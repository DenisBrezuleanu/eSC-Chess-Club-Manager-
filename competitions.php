<?php
require_once 'includes/auth_check.php';
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/competition_helpers.php';

$message = '';
$messageType = '';
$selectedCompetitionId = (int)($_GET['competition_id'] ?? ($_POST['id_competitie'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

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
            $message = 'Competitia a fost modificata.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO competitions (nume, data, locatie, tip) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $date, $location, $type]);
            $selectedCompetitionId = (int)$pdo->lastInsertId();
            $message = 'Competitia a fost adaugata.';
        }

        $messageType = 'alert-success';
    }

    if ($action === 'delete_competition') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM competitions WHERE id = ?");

        if ($stmt->execute([$id])) {
            $selectedCompetitionId = $selectedCompetitionId === $id ? 0 : $selectedCompetitionId;
            $message = 'Competitia a fost stearsa.';
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
        $message = 'Participantul a fost adaugat sau actualizat in clasament.';
        $messageType = 'alert-success';
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

$pageTitle = 'eSC - Competitii si premii';
require 'includes/header.php';
?>
<section class="page-title">
    <h2>Competitii, premii si clasamente</h2>
    <p>Administreaza competitiile, inscrie participanti si genereaza clasamente server-side.</p>
</section>

<?php if ($message): ?>
    <div class="<?= e($messageType) ?>"><?= e($message) ?></div>
<?php endif; ?>

<section class="form-grid">
    <form action="competitions.php" method="POST" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_competition">
        <input type="hidden" name="id" value="<?= e($editCompetition['id'] ?? '') ?>">

        <h3><?= $editCompetition ? 'Editeaza competitie' : 'Adauga competitie' ?></h3>

        <div class="form-field">
            <label for="competition-nume">Nume competitie</label>
            <input type="text" id="competition-nume" name="nume" value="<?= e($editCompetition['nume'] ?? '') ?>" required>
        </div>

        <div class="form-field">
            <label for="competition-data">Data</label>
            <input type="date" id="competition-data" name="data" value="<?= e($editCompetition['data'] ?? '') ?>" required>
        </div>

        <div class="form-field">
            <label for="competition-locatie">Locatie</label>
            <input type="text" id="competition-locatie" name="locatie" value="<?= e($editCompetition['locatie'] ?? '') ?>" required>
        </div>

        <div class="form-field">
            <label for="competition-tip">Tip</label>
            <select id="competition-tip" name="tip" required>
                <?php foreach (['Clasic', 'Rapid', 'Blitz', 'Intern', 'Extern'] as $type): ?>
                    <option value="<?= e($type) ?>"<?= selected_attr($editCompetition['tip'] ?? 'Clasic', $type) ?>>
                        <?= e($type) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit"><?= $editCompetition ? 'Modifica' : 'Adauga' ?> competitie</button>
        <?php if ($editCompetition): ?>
            <a class="button secondary" href="competitions.php?competition_id=<?= e($editCompetition['id']) ?>">Anuleaza editarea</a>
        <?php endif; ?>
    </form>

    <form action="competitions.php" method="POST" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_participant">

        <h3>Adauga participant</h3>

        <div class="form-field">
            <label for="participant-competition">Competitie</label>
            <select id="participant-competition" name="id_competitie" required>
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
                <?php foreach ($members as $member): ?>
                    <option value="<?= e($member['id']) ?>"><?= e($member['nume']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field">
            <label for="participant-score">Punctaj obtinut</label>
            <input type="number" id="participant-score" name="punctaj_obtinut" min="0" step="0.01" required>
        </div>

        <button type="submit"<?= (!$competitions || !$members) ? ' ' : '' ?>>Adauga participant</button>
        <?php if (!$competitions || !$members): ?>
            <p class="form-note">Ai nevoie de cel putin o competitie si un membru.</p>
        <?php endif; ?>
    </form>
</section>

<section class="content-grid">
    <div class="panel">
        <h3>Competitii</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nume</th>
                        <th>Data</th>
                        <th>Locatie</th>
                        <th>Tip</th>
                        <th>Actiuni</th>
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
                                    <a class="button compact secondary" href="competitions.php?edit=<?= e($competition['id']) ?>&competition_id=<?= e($competition['id']) ?>">Modifica</a>
                                    <form action="competitions.php" method="POST" class="inline-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete_competition">
                                        <input type="hidden" name="id" value="<?= e($competition['id']) ?>">
                                        <input type="hidden" name="id_competitie" value="<?= e($competition['id']) ?>">
                                        <button type="submit" class="compact danger">Sterge</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$competitions): ?>
                        <tr>
                            <td colspan="5" class="centered">Nu exista competitii inregistrate.</td>
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

<section class="form-grid">
    <form action="competitions.php" method="POST" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_award">

        <h3>Creeaza premiu</h3>

        <div class="form-field">
            <label for="award-nume">Nume premiu</label>
            <input type="text" id="award-nume" name="award_nume" required>
        </div>

        <div class="form-field">
            <label for="award-descriere">Descriere</label>
            <textarea id="award-descriere" name="descriere" rows="3"></textarea>
        </div>

        <button type="submit">Salveaza premiu</button>
    </form>

    <form action="competitions.php" method="POST" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="assign_award">

        <h3>Acorda premiu</h3>

        <div class="form-field">
            <label for="assign-award">Premiu</label>
            <select id="assign-award" name="id_award" required>
                <?php foreach ($awards as $award): ?>
                    <option value="<?= e($award['id']) ?>"><?= e($award['nume']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field">
            <label for="assign-member">Membru</label>
            <select id="assign-member" name="id_membru" required>
                <?php foreach ($members as $member): ?>
                    <option value="<?= e($member['id']) ?>"><?= e($member['nume']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field">
            <label for="assign-competition">Competitie</label>
            <select id="assign-competition" name="id_competitie">
                <option value="">Fara competitie</option>
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

        <button type="submit"<?= (!$awards || !$members) ? ' disabled' : '' ?>>Acorda premiu</button>
        <?php if (!$awards || !$members): ?>
            <p class="form-note">Ai nevoie de cel putin un premiu si un membru.</p>
        <?php endif; ?>
    </form>
</section>

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
                        <td colspan="4" class="centered">Nu exista premii acordate.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
