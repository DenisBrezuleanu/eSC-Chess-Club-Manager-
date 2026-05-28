<?php
require_once 'includes/auth_check.php';
require_once 'includes/config.php';
require_once 'includes/functions.php';

$members = $pdo->query("SELECT id, nume FROM members ORDER BY nume ASC")->fetchAll();
$memberId = is_member() ? current_member_id() : (int)($_GET['member_id'] ?? 0);

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

$pageTitle = is_member() ? 'eSC - Istoricul meu' : 'eSC - Istoric performanțe';
require 'includes/header.php';
?>
<section class="page-title">
    <h2><?= is_member() ? 'Istoricul meu de performanțe' : 'Istoric performanțe' ?></h2>
    <p>Timeline-ul este construit dintr-un JOIN SQL între membri, participări și competiții.</p>
</section>

<?php if (!is_member()): ?>
<form action="performance_history.php" method="GET" class="form-card compact-form">
    <div class="form-field">
        <label for="history-member">Membru</label>
        <select id="history-member" name="member_id" required>
            <option value="">Alege membrul</option>
            <?php foreach ($members as $member): ?>
                <option value="<?= e($member['id']) ?>"<?= selected_attr($memberId, $member['id']) ?>>
                    <?= e($member['nume']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit">Afișează istoric</button>
</form>
<?php else: ?>
    <div class="alert-success"><?= e(role_read_only_notice('istoricul tău competițional')) ?></div>
<?php endif; ?>

<section class="panel">
    <h3><?= $selectedMember ? e($selectedMember['nume']) : 'Niciun membru selectat' ?></h3>

    <?php if ($history): ?>
        <div class="timeline" role="list" aria-label="Timeline performanțe">
            <?php foreach ($history as $item): ?>
                <div class="timeline-item" role="listitem">
                    <div class="timeline-content">
                        <span class="timeline-date"><?= e($item['data']) ?></span>
                        <h4><?= e($item['nume']) ?></h4>
                        <p><?= e($item['locatie']) ?>, <?= e($item['tip']) ?></p>
                        <p>
                            Loc <?= e($item['loc_clasament']) ?>,
                            punctaj <?= e(number_format((float)$item['punctaj_obtinut'], 2, '.', '')) ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="empty-state">Nu există participări în competiții pentru acest membru.</p>
    <?php endif; ?>
</section>
<?php require 'includes/footer.php'; ?>
