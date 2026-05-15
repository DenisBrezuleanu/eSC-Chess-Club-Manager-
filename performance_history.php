<?php
require_once 'includes/auth_check.php';
require_once 'includes/config.php';
require_once 'includes/functions.php';

$members = $pdo->query("SELECT id, nume FROM members ORDER BY nume ASC")->fetchAll();
$memberId = (int)($_GET['member_id'] ?? 0);

if ($memberId === 0 && $members) {
    $memberId = (int)$members[0]['id'];
}

$selectedMember = null;
$history = [];

if ($memberId > 0) {
    $stmt = $pdo->prepare("SELECT id, nume FROM members WHERE id = ?");
    $stmt->execute([$memberId]);
    $selectedMember = $stmt->fetch();

    $stmt = $pdo->prepare("
        SELECT competitions.id, competitions.nume, competitions.data, competitions.locatie, competitions.tip,
               competition_participants.punctaj_obtinut,
               (
                   SELECT COUNT(*) + 1
                   FROM competition_participants AS ranking
                   WHERE ranking.id_competitie = competitions.id
                     AND ranking.punctaj_obtinut > competition_participants.punctaj_obtinut
               ) AS loc_clasament
        FROM competition_participants
        INNER JOIN competitions ON competitions.id = competition_participants.id_competitie
        WHERE competition_participants.id_membru = ?
        ORDER BY competitions.data DESC, competitions.id DESC
    ");
    $stmt->execute([$memberId]);
    $history = $stmt->fetchAll();
}

$pageTitle = 'eSC - Istoric performante';
require 'includes/header.php';
?>
<section class="page-title">
    <h2>Istoric performante</h2>
    <p>Timeline-ul este construit dintr-un JOIN SQL intre membri, participari si competitii.</p>
</section>

<form action="performance_history.php" method="GET" class="form-card compact-form">
    <div class="form-field">
        <label for="history-member">Membru</label>
        <select id="history-member" name="member_id" required>
            <?php foreach ($members as $member): ?>
                <option value="<?= e($member['id']) ?>"<?= selected_attr($memberId, $member['id']) ?>>
                    <?= e($member['nume']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit">Afiseaza istoric</button>
</form>

<section class="panel">
    <h3><?= $selectedMember ? e($selectedMember['nume']) : 'Niciun membru selectat' ?></h3>

    <?php if ($history): ?>
        <div class="timeline" aria-label="Timeline performante">
            <?php foreach ($history as $item): ?>
                <article class="timeline-item">
                    <div class="timeline-content">
                        <span class="timeline-date"><?= e($item['data']) ?></span>
                        <h4><?= e($item['nume']) ?></h4>
                        <p><?= e($item['locatie']) ?>, <?= e($item['tip']) ?></p>
                        <p>
                            Loc <?= e($item['loc_clasament']) ?>,
                            punctaj <?= e(number_format((float)$item['punctaj_obtinut'], 2, '.', '')) ?>
                        </p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="empty-state">Nu exista participari in competitii pentru acest membru.</p>
    <?php endif; ?>
</section>
<?php require 'includes/footer.php'; ?>
