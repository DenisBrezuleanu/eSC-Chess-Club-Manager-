<?php
require_once 'includes/auth_check.php';
require_once 'includes/config.php';
require_once 'includes/functions.php';

$role = current_user_role();
$roleLabel = current_user_role_label();
$pageTitle = 'eSC - Panou ' . $roleLabel;
$flashError = take_flash_error();
$today = date('Y-m-d');

$stats = [
    'members' => (int)$pdo->query("SELECT COUNT(*) FROM members")->fetchColumn(),
    'coaches' => (int)$pdo->query("SELECT COUNT(*) FROM coaches")->fetchColumn(),
    'rooms' => (int)$pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn(),
    'competitions' => (int)$pdo->query("SELECT COUNT(*) FROM competitions")->fetchColumn(),
    'expenses' => (float)$pdo->query("SELECT COALESCE(SUM(total), 0) FROM travel_expenses")->fetchColumn(),
];

$stmt = $pdo->prepare("SELECT nume, locatie FROM competitions WHERE data = ? ORDER BY nume ASC");
$stmt->execute([$today]);
$todayCompetitions = $stmt->fetchAll();

$dashboardCards = [];
$panels = [];
$quickActions = [];

if ($role === 'admin') {
    $dashboardTitle = 'Panou administrator';
    $dashboardIntro = 'Privire rapidă peste club și scurtături către administrare.';

    $dashboardCards = [
        ['href' => 'members.php', 'value' => $stats['members'], 'label' => 'Membri'],
        ['href' => 'coaches.php', 'value' => $stats['coaches'], 'label' => 'Antrenori'],
        ['href' => 'rooms.php', 'value' => $stats['rooms'], 'label' => 'Săli'],
        ['href' => 'competitions.php', 'value' => $stats['competitions'], 'label' => 'Competiții'],
    ];

    $quickActions = [
        ['href' => 'members.php', 'label' => 'Import membri'],
        ['href' => 'competitions.php', 'label' => 'Competiții și premii'],
        ['href' => 'export_data_json.php', 'label' => 'Export JSON'],
        ['href' => 'export_data_xml.php', 'label' => 'Export XML', 'secondary' => true],
        ['href' => 'travel_report.php', 'label' => 'Raport deconturi', 'secondary' => true],
    ];
} elseif ($role === 'coach') {
    $coachId = current_coach_id();
    $coachName = 'Antrenor';

    if ($coachId > 0) {
        $stmt = $pdo->prepare("SELECT nume FROM coaches WHERE id = ?");
        $stmt->execute([$coachId]);
        $coachName = $stmt->fetchColumn() ?: $coachName;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE id_antrenor_asociat = ?");
    $stmt->execute([$coachId]);
    $assignedMembers = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM activities WHERE data >= ?");
    $stmt->execute([$today]);
    $upcomingActivitiesCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT activities.nume_activitate, activities.data, activities.ora_start, rooms.nume AS sala
        FROM activities
        INNER JOIN rooms ON rooms.id = activities.id_sala
        WHERE activities.data >= ?
        ORDER BY activities.data ASC, activities.ora_start ASC
        LIMIT 5
    ");
    $stmt->execute([$today]);
    $upcomingActivities = $stmt->fetchAll();

    $dashboardTitle = 'Panou antrenor';
    $dashboardIntro = 'Bun venit, ' . $coachName . '. Ai la îndemână programul, jucătorii și competițiile.';

    $dashboardCards = [
        ['href' => 'activities.php', 'value' => $upcomingActivitiesCount, 'label' => 'Activități viitoare'],
        ['href' => 'members.php', 'value' => $assignedMembers, 'label' => 'Jucători asociați'],
        ['href' => 'competitions.php', 'value' => $stats['competitions'], 'label' => 'Competiții'],
        ['href' => 'performance_history.php', 'value' => $stats['members'], 'label' => 'Istoric jucători'],
    ];

    $quickActions = [
        ['href' => 'activities.php', 'label' => 'Planifică activitate'],
        ['href' => 'competitions.php', 'label' => 'Adaugă punctaje'],
        ['href' => 'members.php', 'label' => 'Vezi jucători', 'secondary' => true],
        ['href' => 'performance_history.php', 'label' => 'Verifică istoric', 'secondary' => true],
    ];

    $panels[] = [
        'title' => 'Program apropiat',
        'items' => array_map(static function (array $activity): array {
            return [
                'title' => $activity['nume_activitate'],
                'meta' => $activity['data'] . ' la ' . substr($activity['ora_start'], 0, 5) . ' - ' . $activity['sala'],
            ];
        }, $upcomingActivities),
        'empty' => 'Nu există activități viitoare planificate.',
    ];
} else {
    $memberId = current_member_id();
    $member = null;

    if ($memberId > 0) {
        $stmt = $pdo->prepare("
            SELECT members.*, coaches.nume AS nume_antrenor
            FROM members
            LEFT JOIN coaches ON coaches.id = members.id_antrenor_asociat
            WHERE members.id = ?
        ");
        $stmt->execute([$memberId]);
        $member = $stmt->fetch();
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM competition_participants WHERE id_membru = ?");
    $stmt->execute([$memberId]);
    $participationsCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM member_awards WHERE id_membru = ?");
    $stmt->execute([$memberId]);
    $awardsCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT competitions.nume, competitions.data, competitions.locatie, competition_participants.punctaj_obtinut
        FROM competition_participants
        INNER JOIN competitions ON competitions.id = competition_participants.id_competitie
        WHERE competition_participants.id_membru = ?
        ORDER BY competitions.data DESC, competitions.id DESC
        LIMIT 5
    ");
    $stmt->execute([$memberId]);
    $recentResults = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT activities.nume_activitate, activities.data, activities.ora_start, rooms.nume AS sala
        FROM activities
        INNER JOIN rooms ON rooms.id = activities.id_sala
        WHERE activities.data >= ?
        ORDER BY activities.data ASC, activities.ora_start ASC
        LIMIT 4
    ");
    $stmt->execute([$today]);
    $upcomingActivities = $stmt->fetchAll();

    $memberName = $member['nume'] ?? 'Membru';
    $dashboardTitle = 'Panoul meu';
    $dashboardIntro = 'Bun venit, ' . $memberName . '. Aici vezi rapid profilul, programul și rezultatele tale.';

    $dashboardCards = [
        ['href' => 'members.php', 'value' => $member ? ucfirst($member['tip_membru']) : '-', 'label' => 'Profil'],
        ['href' => 'performance_history.php', 'value' => $participationsCount, 'label' => 'Participări'],
        ['href' => 'competitions.php', 'value' => $awardsCount, 'label' => 'Premii'],
        ['href' => 'activities.php', 'value' => count($upcomingActivities), 'label' => 'Activități apropiate'],
    ];

    $quickActions = [
        ['href' => 'members.php', 'label' => 'Vezi profilul'],
        ['href' => 'performance_history.php', 'label' => 'Istoricul meu'],
        ['href' => 'activities.php', 'label' => 'Program activități', 'secondary' => true],
        ['href' => 'competitions.php', 'label' => 'Competiții', 'secondary' => true],
    ];

    $panels[] = [
        'title' => 'Profil rapid',
        'items' => $member ? [
            ['title' => 'Nivel joc', 'meta' => $member['nivel_joc'] ?: '-'],
            ['title' => 'Antrenor asociat', 'meta' => $member['nume_antrenor'] ?: 'Fără antrenor asociat'],
        ] : [],
        'empty' => 'Contul nu este legat de un membru existent.',
    ];

    $panels[] = [
        'title' => 'Rezultate recente',
        'items' => array_map(static function (array $result): array {
            return [
                'title' => $result['nume'],
                'meta' => $result['data'] . ' - ' . $result['locatie'] . ', punctaj ' . number_format((float)$result['punctaj_obtinut'], 2, '.', ''),
            ];
        }, $recentResults),
        'empty' => 'Nu ai încă participări înregistrate.',
    ];

    $panels[] = [
        'title' => 'Program apropiat',
        'items' => array_map(static function (array $activity): array {
            return [
                'title' => $activity['nume_activitate'],
                'meta' => $activity['data'] . ' la ' . substr($activity['ora_start'], 0, 5) . ' - ' . $activity['sala'],
            ];
        }, $upcomingActivities),
        'empty' => 'Nu există activități viitoare planificate.',
    ];
}

require 'includes/header.php';
?>
<section class="page-title">
    <h2><?= e($dashboardTitle) ?></h2>
    <p><?= e($dashboardIntro) ?></p>
</section>

<?php if ($flashError): ?>
    <div class="alert-error"><?= e($flashError) ?></div>
<?php endif; ?>

<section class="dashboard-grid" aria-label="Rezumat <?= e($roleLabel) ?>">
    <?php foreach ($dashboardCards as $card): ?>
        <a class="metric-card" href="<?= e($card['href']) ?>">
            <strong><?= e($card['value']) ?></strong>
            <span><?= e($card['label']) ?></span>
        </a>
    <?php endforeach; ?>
</section>

<section class="content-grid">
    <div class="panel">
        <h3>Competiții astăzi</h3>
        <?php if ($todayCompetitions): ?>
            <ul class="clean-list">
                <?php foreach ($todayCompetitions as $competition): ?>
                    <li>
                        <strong><?= e($competition['nume']) ?></strong>
                        <span><?= e($competition['locatie']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="empty-state">Nu există competiții programate pentru astăzi.</p>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h3>Acțiuni utile</h3>
        <div class="action-list">
            <?php foreach ($quickActions as $action): ?>
                <a class="button<?= !empty($action['secondary']) ? ' secondary' : '' ?>" href="<?= e($action['href']) ?>">
                    <?= e($action['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php if (is_admin()): ?>
            <p class="form-note">Total deconturi înregistrate: <strong><?= e(format_money($stats['expenses'])) ?> lei</strong></p>
        <?php endif; ?>
    </div>

    <?php foreach ($panels as $panel): ?>
        <div class="panel">
            <h3><?= e($panel['title']) ?></h3>
            <?php if ($panel['items']): ?>
                <ul class="clean-list">
                    <?php foreach ($panel['items'] as $item): ?>
                        <li>
                            <strong><?= e($item['title']) ?></strong>
                            <span><?= e($item['meta']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="empty-state"><?= e($panel['empty']) ?></p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</section>
<?php require 'includes/footer.php'; ?>
