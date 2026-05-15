<?php
require_once 'includes/auth_check.php';
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/competition_helpers.php';

$competitions = $pdo->query("SELECT * FROM competitions ORDER BY data DESC, id DESC")->fetchAll();
$competitionId = (int)($_GET['competition_id'] ?? 0);

if ($competitionId === 0 && $competitions) {
    $competitionId = (int)$competitions[0]['id'];
}

$selectedCompetition = null;
if ($competitionId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM competitions WHERE id = ?");
    $stmt->execute([$competitionId]);
    $selectedCompetition = $stmt->fetch();
}

$leaderboard = $competitionId > 0 ? get_competition_leaderboard($pdo, $competitionId) : [];

$pageTitle = 'eSC - Clasament competitie';
require 'includes/header.php';
?>
<section class="page-title">
    <h2>Clasament competitie</h2>
    <p>HTML-ul clasamentului este generat de PHP dupa ordonarea punctajelor in SQL.</p>
</section>

<form action="leaderboard.php" method="GET" class="form-card compact-form">
    <div class="form-field">
        <label for="leaderboard-competition">Competitie</label>
        <select id="leaderboard-competition" name="competition_id" required>
            <?php foreach ($competitions as $competition): ?>
                <option value="<?= e($competition['id']) ?>"<?= selected_attr($competitionId, $competition['id']) ?>>
                    <?= e($competition['nume']) ?> - <?= e($competition['data']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit">Afiseaza clasament</button>
</form>

<section class="panel">
    <h3><?= $selectedCompetition ? e($selectedCompetition['nume']) : 'Nicio competitie selectata' ?></h3>
    <?= render_leaderboard_html($leaderboard) ?>
</section>
<?php require 'includes/footer.php'; ?>
