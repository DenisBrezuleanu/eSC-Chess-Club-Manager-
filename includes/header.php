<?php
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? 'eSC - Chess Club Manager';
$bodyClass = $bodyClass ?? '';
$includePrintCss = $includePrintCss ?? false;

$navItemsByRole = [
    'admin' => [
        ['href' => 'index.php', 'label' => 'Acasă', 'match' => ['index.php']],
        ['href' => 'members.php', 'label' => 'Membri', 'match' => ['members.php']],
        ['href' => 'coaches.php', 'label' => 'Antrenori', 'match' => ['coaches.php']],
        ['href' => 'rooms.php', 'label' => 'Săli', 'match' => ['rooms.php']],
        ['href' => 'activities.php', 'label' => 'Activități', 'match' => ['activities.php']],
        ['href' => 'competitions.php', 'label' => 'Competiții', 'match' => ['competitions.php', 'leaderboard.php']],
        ['href' => 'performance_history.php', 'label' => 'Istoric', 'match' => ['performance_history.php']],
        ['href' => 'travel_expenses.php', 'label' => 'Deconturi', 'match' => ['travel_expenses.php']],
        ['href' => 'travel_report.php', 'label' => 'Raport', 'match' => ['travel_report.php']],
    ],
    'coach' => [
        ['href' => 'index.php', 'label' => 'Acasă', 'match' => ['index.php']],
        ['href' => 'activities.php', 'label' => 'Program', 'match' => ['activities.php']],
        ['href' => 'members.php', 'label' => 'Jucători', 'match' => ['members.php']],
        ['href' => 'competitions.php', 'label' => 'Competiții', 'match' => ['competitions.php', 'leaderboard.php']],
        ['href' => 'performance_history.php', 'label' => 'Istoric', 'match' => ['performance_history.php']],
    ],
    'member' => [
        ['href' => 'index.php', 'label' => 'Acasă', 'match' => ['index.php']],
        ['href' => 'members.php', 'label' => 'Profil', 'match' => ['members.php']],
        ['href' => 'activities.php', 'label' => 'Program', 'match' => ['activities.php']],
        ['href' => 'competitions.php', 'label' => 'Competiții', 'match' => ['competitions.php', 'leaderboard.php']],
        ['href' => 'performance_history.php', 'label' => 'Istoricul meu', 'match' => ['performance_history.php']],
    ],
];
$navItems = $navItemsByRole[current_user_role()] ?? $navItemsByRole['member'];
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
    <a class="skip-link" href="#main-content">Sari la conținut</a>
    <input type="checkbox" id="menu-toggle" class="menu-toggle" aria-label="Deschide sau închide meniul principal" aria-controls="site-nav">

    <header class="site-header">
        <div>
            <h1>eSC - Chess Club Manager</h1>
            <p>Administrare club, competiții și deconturi</p>
        </div>
        <label for="menu-toggle" class="menu-button">
            <span class="hamburger-lines" aria-hidden="true"></span>
            <span>Meniu</span>
        </label>
    </header>

    <nav id="site-nav" class="site-nav" aria-label="Navigație principală">
        <?php foreach ($navItems as $item): ?>
            <?php $isActive = is_active_page($item['match']); ?>
            <a href="<?= e($item['href']) ?>" class="<?= $isActive ? 'active' : '' ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
                <?= e($item['label']) ?>
            </a>
        <?php endforeach; ?>
        <a href="docs/report.html" target="_blank">Documentație</a>
        <a href="logout.php">Ieșire<?= isset($_SESSION['username']) ? ' (' . e($_SESSION['username']) . ' - ' . current_user_role_label() . ')' : '' ?></a>
    </nav>

    <?php require __DIR__ . '/../plugin_notificari.php'; ?>

    <main id="main-content" class="page-shell" tabindex="-1">
