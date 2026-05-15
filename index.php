<?php
require_once 'includes/auth_check.php';
require_once 'includes/config.php';
require_once 'includes/functions.php';

$pageTitle = 'eSC - Panou principal';

$stats = [
    'members' => (int)$pdo->query("SELECT COUNT(*) FROM members")->fetchColumn(),
    'coaches' => (int)$pdo->query("SELECT COUNT(*) FROM coaches")->fetchColumn(),
    'rooms' => (int)$pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn(),
    'competitions' => (int)$pdo->query("SELECT COUNT(*) FROM competitions")->fetchColumn(),
    'expenses' => (float)$pdo->query("SELECT COALESCE(SUM(total), 0) FROM travel_expenses")->fetchColumn(),
];

$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT nume, locatie FROM competitions WHERE data = ? ORDER BY nume ASC");
$stmt->execute([$today]);
$todayCompetitions = $stmt->fetchAll();

require 'includes/header.php';
?>
<section class="page-title">
    <h2>Panou principal</h2>
    <p>Alege modulul de lucru si urmareste rapid starea clubului.</p>
</section>

<section class="dashboard-grid" aria-label="Rezumat club">
    <a class="metric-card" href="members.php">
        <strong><?= e($stats['members']) ?></strong>
        <span>Membri</span>
    </a>
    <a class="metric-card" href="coaches.php">
        <strong><?= e($stats['coaches']) ?></strong>
        <span>Antrenori</span>
    </a>
    <a class="metric-card" href="rooms.php">
        <strong><?= e($stats['rooms']) ?></strong>
        <span>Sali</span>
    </a>
    <a class="metric-card" href="competitions.php">
        <strong><?= e($stats['competitions']) ?></strong>
        <span>Competitii</span>
    </a>
</section>

<section class="content-grid">
    <div class="panel">
        <h3>Competitii astazi</h3>
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
            <p class="empty-state">Nu exista competitii programate pentru astazi.</p>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h3>Exporturi rapide</h3>
        <p>Total deconturi inregistrate: <strong><?= e(format_money($stats['expenses'])) ?> lei</strong></p>
        <div class="action-list">
            <a class="button" href="export_data_json.php">Export JSON</a>
            <a class="button secondary" href="export_data_xml.php">Export XML</a>
            <a class="button secondary" href="travel_report.php">Raport deconturi</a>
        </div>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
