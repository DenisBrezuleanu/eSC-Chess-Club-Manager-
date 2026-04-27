<?php
require_once 'auth_check.php';
require_once 'config.php';

$message = '';
$messageType = '';

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM members WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = "Membrul a fost șters.";
        $messageType = "alert-success";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_edit_member'])) {
        $id = empty($_POST['id']) ? null : (int)$_POST['id'];
        $nume = trim($_POST['nume'] ?? '');
        $tip_membru = trim($_POST['tip_membru'] ?? '');
        $nivel_joc = trim($_POST['nivel_joc'] ?? '');
        $id_antrenor_asociat = empty($_POST['id_antrenor_asociat']) ? null : (int)$_POST['id_antrenor_asociat'];

        if ($id) {
            $stmt = $pdo->prepare("UPDATE members SET nume=?, tip_membru=?, nivel_joc=?, id_antrenor_asociat=? WHERE id=?");
            $stmt->execute([$nume, $tip_membru, $nivel_joc, $id_antrenor_asociat, $id]);
            $message = "Datele membrului au fost modificate.";
            $messageType = "alert-success";
        } else {
            $stmt = $pdo->prepare("INSERT INTO members (nume, tip_membru, nivel_joc, id_antrenor_asociat) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nume, $tip_membru, $nivel_joc, $id_antrenor_asociat]);
            $message = "Membrul a fost adăugat cu succes.";
            $messageType = "alert-success";
        }
    } elseif (isset($_POST['import_csv'])) {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($fileTmpPath, 'r');
            
            if ($handle !== false) {
                // in caz ca avem header
                $firstLine = true;
                $importedCount = 0;
                
                $stmt = $pdo->prepare("INSERT INTO members (nume, tip_membru, nivel_joc, id_antrenor_asociat) VALUES (?, ?, ?, ?)");
                
                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                    if ($firstLine) {
                        $firstLine = false;
                        // verif 
                        if (strtolower(trim($data[0])) == 'nume') {
                            continue;
                        }
                    }
                    
                    if (count($data) >= 3) {
                        $nume = trim($data[0]);
                        $tip_membru = trim($data[1]);
                        $nivel_joc = trim($data[2]);
                        $id_antrenor = isset($data[3]) && is_numeric($data[3]) ? (int)$data[3] : null;
                        
                        if ($id_antrenor === 0) $id_antrenor = null;

                        // validate enum
                        $validTypes = ['junior', 'senior', 'amator', 'pro'];
                        if (!in_array(strtolower($tip_membru), $validTypes)) {
                            $tip_membru = 'amator'; // default fallback
                        }
                        
                        $stmt->execute([$nume, strtolower($tip_membru), $nivel_joc, $id_antrenor]);
                        $importedCount++;
                    }
                }
                fclose($handle);
                $message = "Au fost importați $importedCount membri din fișierul CSV.";
                $messageType = "alert-success";
            } else {
                $message = "Eroare la deschiderea fișierului CSV.";
                $messageType = "alert-error";
            }
        } else {
            $message = "Te rugăm să alegi un fișier valid.";
            $messageType = "alert-error";
        }
    }
}

// Fetch lists
$coaches = $pdo->query("SELECT * FROM coaches")->fetchAll(PDO::FETCH_ASSOC);

$members = $pdo->query("SELECT members.*, coaches.nume AS nume_antrenor 
                        FROM members 
                        LEFT JOIN coaches ON members.id_antrenor_asociat = coaches.id
                        ORDER BY members.id DESC")->fetchAll(PDO::FETCH_ASSOC);

$editMember = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editMember = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eSC - Gestiune Membri</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>eSC - Chess Club Manager</h1>
    </header>
    
    <nav>
        <a href="index.php">Acasa</a>
        <a href="coaches.php">Antrenori</a>
        <a href="rooms.php">Sali</a>
        <a href="activities.php">Activitati / Calendar</a>
        <a href="members.php">Membri</a>
        <a href="logout.php">Logout</a>
    </nav>
    
    <main>
        <h2>Gestiunea Membrilor Clubului</h2>

        <?php if ($message): ?>
            <div class="<?= htmlspecialchars($messageType) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div style="display: flex; flex-wrap: wrap; gap: 2rem; align-items: flex-start; justify-content: center; margin-bottom: 2rem;">
            <form action="members.php" method="POST" style="flex: 1; min-width: 300px; margin: 0;">
                <h3><?= $editMember ? 'Editează Membru' : 'Adaugă Membru' ?></h3>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="add_edit_member" value="1">
                <input type="hidden" name="id" value="<?= htmlspecialchars($editMember['id'] ?? '') ?>">
                
                <label>Nume Membru:</label>
                <input type="text" name="nume" value="<?= htmlspecialchars($editMember['nume'] ?? '') ?>" required>
                
                <label>Tip Membru:</label>
                <select name="tip_membru" required>
                    <?php 
                    $options = ['junior', 'senior', 'amator', 'pro'];
                    foreach ($options as $opt) {
                        $selected = (isset($editMember['tip_membru']) && strtolower($editMember['tip_membru']) === $opt) ? 'selected' : '';
                        echo "<option value=\"$opt\" $selected>" . ucfirst($opt) . "</option>";
                    }
                    ?>
                </select>

                <label>Nivel Joc (ex. ELO/Titlu):</label>
                <input type="text" name="nivel_joc" value="<?= htmlspecialchars($editMember['nivel_joc'] ?? '') ?>">
                
                <label>Antrenor Asociat (Opțional):</label>
                <select name="id_antrenor_asociat">
                    <option value="">-- Fără antrenor --</option>
                    <?php foreach ($coaches as $coach): ?>
                        <option value="<?= htmlspecialchars($coach['id']) ?>" <?= (isset($editMember['id_antrenor_asociat']) && $editMember['id_antrenor_asociat'] == $coach['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($coach['nume']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit"><?= $editMember ? 'Modifică' : 'Adaugă' ?></button>
                <?php if ($editMember): ?>
                    <a href="members.php" style="text-align: center; display: block; margin-top: 10px;">Anulează editarea</a>
                <?php endif; ?>
            </form>


            <form action="members.php" method="POST" enctype="multipart/form-data" style="flex: 1; min-width: 300px; margin: 0;">
                <h3>Importă Membri CSV</h3>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="import_csv" value="1">
                <p style="font-size: 0.9rem; margin-bottom: 10px;">Format așteptat: <strong>nume, tip_membru, nivel_joc, id_antrenor</strong></p>
                <input type="file" name="csv_file" accept=".csv" required>
                <button type="submit">Importă CSV</button>
            </form>
        </div>

        <h3>Lista Membrilor</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nume</th>
                    <th>Tip Membru</th>
                    <th>Nivel Joc</th>
                    <th>Antrenor Asociat</th>
                    <th>Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $member): ?>
                <tr>
                    <td><?= htmlspecialchars($member['id']) ?></td>
                    <td><?= htmlspecialchars($member['nume']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($member['tip_membru'])) ?></td>
                    <td><?= htmlspecialchars($member['nivel_joc']) ?></td>
                    <td><?= htmlspecialchars($member['nume_antrenor'] ?? 'N/A') ?></td>
                    <td>
                        <a href="members.php?edit=<?= $member['id'] ?>">Modifică</a> |
                        <a href="members.php?delete=<?= $member['id'] ?>" onclick="return confirm('Sigur ștergi acest membru?');">Șterge</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

    <footer>
        <p>&copy; 2026 eSC Chess Club Manager. Toate drepturile rezervate.</p>
    </footer>
</body>
</html>