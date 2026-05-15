<?php
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? 'eSC - Chess Club Manager';
$bodyClass = $bodyClass ?? '';
$includePrintCss = $includePrintCss ?? false;

$navItems = [
    ['href' => 'index.php', 'label' => 'Acasa', 'match' => ['index.php']],
    ['href' => 'coaches.php', 'label' => 'Antrenori', 'match' => ['coaches.php']],
    ['href' => 'rooms.php', 'label' => 'Sali', 'match' => ['rooms.php']],
    ['href' => 'activities.php', 'label' => 'Activitati', 'match' => ['activities.php']],
    ['href' => 'members.php', 'label' => 'Membri', 'match' => ['members.php']],
    ['href' => 'competitions.php', 'label' => 'Competitii', 'match' => ['competitions.php', 'leaderboard.php']],
    ['href' => 'performance_history.php', 'label' => 'Istoric', 'match' => ['performance_history.php']],
    ['href' => 'travel_expenses.php', 'label' => 'Deconturi', 'match' => ['travel_expenses.php']],
    ['href' => 'travel_report.php', 'label' => 'Raport', 'match' => ['travel_report.php']],
];
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if ($includePrintCss): ?>
        <link rel="stylesheet" href="assets/css/print.css" media="print">
    <?php endif; ?>
</head>
<body class="<?= e($bodyClass) ?>">
    <input type="checkbox" id="menu-toggle" class="menu-toggle" aria-label="Meniu principal">

    <header class="site-header">
        <div>
            <h1>eSC - Chess Club Manager</h1>
            <p>Administrare club, competitii si deconturi</p>
        </div>
        <label for="menu-toggle" class="menu-button">
            <span class="hamburger-lines" aria-hidden="true"></span>
            <span>Meniu</span>
        </label>
    </header>

    <nav class="site-nav" aria-label="Navigatie principala">
        <?php foreach ($navItems as $item): ?>
            <?php $isActive = is_active_page($item['match']); ?>
            <a href="<?= e($item['href']) ?>" class="<?= $isActive ? 'active' : '' ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
                <?= e($item['label']) ?>
            </a>
        <?php endforeach; ?>
        <a href="logout.php">Logout<?= isset($_SESSION['username']) ? ' (' . e($_SESSION['username']) . ')' : '' ?></a>
    </nav>

    <?php require __DIR__ . '/../plugin_notificari.php'; ?>

    <main class="page-shell">
