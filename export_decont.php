<?php
require_once 'includes/auth_check.php';
require_once 'includes/config.php';

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM travel_expenses WHERE id = ? ORDER BY data DESC, id DESC");
    $stmt->execute([$id]);
    $expenses = $stmt->fetchAll();
    $filename = 'decont_' . $id . '.csv';
} else {
    $expenses = $pdo->query("SELECT * FROM travel_expenses ORDER BY data DESC, id DESC")->fetchAll();
    $filename = 'deconturi.csv';
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Destinatie', 'Data', 'Scop', 'Cost transport', 'Cost cazare', 'Cost masa', 'Total']);

foreach ($expenses as $expense) {
    fputcsv($output, [
        $expense['id'],
        $expense['destinatie'],
        $expense['data'],
        $expense['scop'],
        $expense['cost_transport'],
        $expense['cost_cazare'],
        $expense['cost_masa'],
        $expense['total'],
    ]);
}

fclose($output);
exit();
