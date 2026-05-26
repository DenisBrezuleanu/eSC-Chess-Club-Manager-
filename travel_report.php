<?php
require_once 'includes/auth_check.php';
require_once 'includes/config.php';
require_once 'includes/functions.php';

role_guard(is_admin(), 'Doar administratorul poate accesa rapoartele financiare.');

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM travel_expenses WHERE id = ?");
    $stmt->execute([$id]);
    $expenses = $stmt->fetchAll();
} else {
    $expenses = $pdo->query("SELECT * FROM travel_expenses ORDER BY data DESC, id DESC")->fetchAll();
}

$grandTotal = 0;
foreach ($expenses as $expense) {
    $grandTotal += (float)$expense['total'];
}

$pageTitle = 'eSC - Raport decont';
$includePrintCss = true;
require 'includes/header.php';
?>
<section class="page-title">
    <h2>Raport decont deplasari</h2>
    <p>Raport pregatit pentru tiparire in alb-negru.</p>
</section>

<div class="panel no-print">
    <div class="section-heading">
        <p class="print-instruction">Pentru tiparire, apasa Ctrl+P / Cmd+P.</p>
        <div class="action-list">
            <a class="button secondary" href="travel_expenses.php">Inapoi la deconturi</a>
            <a class="button" href="export_decont.php<?= $id > 0 ? '?id=' . e($id) : '' ?>">Export CSV</a>
        </div>
    </div>
</div>

<section class="print-report">
    <h3><?= $id > 0 ? 'Decont individual' : 'Raport general deconturi' ?></h3>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Destinatie</th>
                    <th>Data</th>
                    <th>Scop</th>
                    <th>Transport</th>
                    <th>Cazare</th>
                    <th>Masa</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($expenses as $expense): ?>
                    <tr>
                        <td><?= e($expense['id']) ?></td>
                        <td><?= e($expense['destinatie']) ?></td>
                        <td><?= e($expense['data']) ?></td>
                        <td><?= e($expense['scop']) ?></td>
                        <td><?= e(format_money($expense['cost_transport'])) ?> lei</td>
                        <td><?= e(format_money($expense['cost_cazare'])) ?> lei</td>
                        <td><?= e(format_money($expense['cost_masa'])) ?> lei</td>
                        <td><strong><?= e(format_money($expense['total'])) ?> lei</strong></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$expenses): ?>
                    <tr>
                        <td colspan="8" class="centered">Nu exista deconturi pentru raport.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="7">Total raport</th>
                    <th><?= e(format_money($grandTotal)) ?> lei</th>
                </tr>
            </tfoot>
        </table>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
