<?php
require_once 'includes/auth_check.php';
require_once 'includes/config.php';

$data = [
    'generated_at' => date(DATE_ATOM),
    'members' => $pdo->query("SELECT * FROM members ORDER BY id ASC")->fetchAll(),
    'competitions' => $pdo->query("SELECT * FROM competitions ORDER BY data DESC, id DESC")->fetchAll(),
    'competition_participants' => $pdo->query("SELECT * FROM competition_participants ORDER BY id_competitie ASC, punctaj_obtinut DESC")->fetchAll(),
    'awards' => $pdo->query("SELECT * FROM awards ORDER BY id ASC")->fetchAll(),
    'member_awards' => $pdo->query("SELECT * FROM member_awards ORDER BY data_acordare DESC, id DESC")->fetchAll(),
    'travel_expenses' => $pdo->query("SELECT * FROM travel_expenses ORDER BY data DESC, id DESC")->fetchAll(),
];

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="esc_export.json"');

echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit();
