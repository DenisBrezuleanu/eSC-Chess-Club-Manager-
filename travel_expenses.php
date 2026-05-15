<?php
require_once 'includes/auth_check.php';
require_once 'includes/config.php';
require_once 'includes/functions.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM travel_expenses WHERE id = ?");

        if ($stmt->execute([$id])) {
            $message = 'Decontul a fost sters.';
            $messageType = 'alert-success';
        }
    }

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $destination = trim($_POST['destinatie'] ?? '');
        $date = trim($_POST['data'] ?? '');
        $purpose = trim($_POST['scop'] ?? '');
        $transport = (float)($_POST['cost_transport'] ?? 0);
        $accommodation = (float)($_POST['cost_cazare'] ?? 0);
        $meals = (float)($_POST['cost_masa'] ?? 0);
        $total = $transport + $accommodation + $meals;

        if ($id > 0) {
            $stmt = $pdo->prepare("
                UPDATE travel_expenses
                SET destinatie = ?, data = ?, scop = ?, cost_transport = ?, cost_cazare = ?, cost_masa = ?, total = ?
                WHERE id = ?
            ");
            $stmt->execute([$destination, $date, $purpose, $transport, $accommodation, $meals, $total, $id]);
            $message = 'Decontul a fost modificat. Totalul a fost recalculat in PHP.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO travel_expenses (destinatie, data, scop, cost_transport, cost_cazare, cost_masa, total)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$destination, $date, $purpose, $transport, $accommodation, $meals, $total]);
            $message = 'Decontul a fost adaugat. Totalul a fost calculat in PHP.';
        }

        $messageType = 'alert-success';
    }
}

$expenses = $pdo->query("SELECT * FROM travel_expenses ORDER BY data DESC, id DESC")->fetchAll();

$editExpense = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM travel_expenses WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editExpense = $stmt->fetch();
}

$pageTitle = 'eSC - Deconturi deplasari';
require 'includes/header.php';
?>
<section class="page-title">
    <h2>Gestiunea financiara pentru deplasari</h2>
    <p>Totalul nu este introdus manual: PHP il calculeaza dupa submit.</p>
</section>

<?php if ($message): ?>
    <div class="<?= e($messageType) ?>"><?= e($message) ?></div>
<?php endif; ?>

<section class="form-grid">
    <form action="travel_expenses.php" method="POST" class="form-card">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= e($editExpense['id'] ?? '') ?>">

        <h3><?= $editExpense ? 'Editeaza decont' : 'Adauga decont' ?></h3>

        <div class="form-field">
            <label for="expense-destinatie">Destinatie</label>
            <input type="text" id="expense-destinatie" name="destinatie" value="<?= e($editExpense['destinatie'] ?? '') ?>" required>
        </div>

        <div class="form-field">
            <label for="expense-data">Data</label>
            <input type="date" id="expense-data" name="data" value="<?= e($editExpense['data'] ?? '') ?>" required>
        </div>

        <div class="form-field">
            <label for="expense-scop">Scop</label>
            <input type="text" id="expense-scop" name="scop" value="<?= e($editExpense['scop'] ?? '') ?>" required>
        </div>

        <div class="form-row">
            <div class="form-field">
                <label for="expense-transport">Cost transport</label>
                <input type="number" id="expense-transport" name="cost_transport" min="0" step="0.01" value="<?= e($editExpense['cost_transport'] ?? '0.00') ?>" required>
            </div>
            <div class="form-field">
                <label for="expense-cazare">Cost cazare</label>
                <input type="number" id="expense-cazare" name="cost_cazare" min="0" step="0.01" value="<?= e($editExpense['cost_cazare'] ?? '0.00') ?>" required>
            </div>
            <div class="form-field">
                <label for="expense-masa">Cost masa</label>
                <input type="number" id="expense-masa" name="cost_masa" min="0" step="0.01" value="<?= e($editExpense['cost_masa'] ?? '0.00') ?>" required>
            </div>
        </div>

        <?php if ($editExpense): ?>
            <p class="form-note">Total curent: <?= e(format_money($editExpense['total'])) ?> lei</p>
        <?php endif; ?>

        <button type="submit"><?= $editExpense ? 'Modifica' : 'Adauga' ?> decont</button>
        <?php if ($editExpense): ?>
            <a class="button secondary" href="travel_expenses.php">Anuleaza editarea</a>
        <?php endif; ?>
    </form>
</section>

<section class="panel">
    <div class="section-heading">
        <h3>Lista deconturilor</h3>
        <div class="action-list">
            <a class="button secondary" href="travel_report.php">Raport print</a>
            <a class="button secondary" href="export_decont.php">Export CSV</a>
        </div>
    </div>

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
                    <th>Actiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($expenses as $expense): ?>
                    <tr>
                        <td><?= e($expense['id']) ?></td>
                        <td><?= e($expense['destinatie']) ?></td>
                        <td><?= e($expense['data']) ?></td>
                        <td><?= e($expense['scop']) ?></td>
                        <td><?= e(format_money($expense['cost_transport'])) ?></td>
                        <td><?= e(format_money($expense['cost_cazare'])) ?></td>
                        <td><?= e(format_money($expense['cost_masa'])) ?></td>
                        <td><strong><?= e(format_money($expense['total'])) ?></strong></td>
                        <td>
                            <div class="action-list">
                                <a class="button compact secondary" href="travel_expenses.php?edit=<?= e($expense['id']) ?>">Modifica</a>
                                <a class="button compact secondary" href="travel_report.php?id=<?= e($expense['id']) ?>">Raport</a>
                                <a class="button compact secondary" href="export_decont.php?id=<?= e($expense['id']) ?>">CSV</a>
                                <form action="travel_expenses.php" method="POST" class="inline-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= e($expense['id']) ?>">
                                    <button type="submit" class="compact danger">Sterge</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$expenses): ?>
                    <tr>
                        <td colspan="9" class="centered">Nu exista deconturi inregistrate.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
