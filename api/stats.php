<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

$today = date('Y-m-d');

$membersCount = (int)$pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();

$stmt = $pdo->prepare("
    SELECT id, nume, data, locatie, tip
    FROM competitions
    WHERE data = ?
    ORDER BY nume ASC
");
$stmt->execute([$today]);
$todayCompetitions = $stmt->fetchAll();

echo json_encode(
    [
        'generated_at' => date(DATE_ATOM),
        'members_count' => $membersCount,
        'competition_today' => count($todayCompetitions) > 0,
        'today_competitions' => $todayCompetitions,
    ],
    JSON_PRETTY_PRINT
);
