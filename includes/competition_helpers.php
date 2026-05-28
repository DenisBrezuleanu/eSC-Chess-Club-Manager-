<?php
require_once __DIR__ . '/functions.php';

function get_competition_leaderboard(PDO $pdo, int $competitionId): array
{
    $stmt = $pdo->prepare("
        SELECT members.id, members.nume, members.tip_membru, members.nivel_joc, competition_participants.punctaj_obtinut
        FROM competition_participants
        INNER JOIN members ON members.id = competition_participants.id_membru
        WHERE competition_participants.id_competitie = ?
        ORDER BY competition_participants.punctaj_obtinut DESC, members.nume ASC
    ");
    $stmt->execute([$competitionId]);

    return $stmt->fetchAll();
}

function render_leaderboard_html(array $participants): string
{
    if (count($participants) === 0) {
        return '<p class="empty-state">Nu există participanți pentru această competiție.</p>';
    }

    ob_start();
    ?>
    <div class="table-responsive">
        <table class="leaderboard-table">
            <thead>
                <tr>
                    <th>Loc</th>
                    <th>Membru</th>
                    <th>Tip</th>
                    <th>Nivel joc</th>
                    <th>Punctaj</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($participants as $index => $participant): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= e($participant['nume']) ?></td>
                        <td><?= e(ucfirst($participant['tip_membru'])) ?></td>
                        <td><?= e($participant['nivel_joc'] ?: '-') ?></td>
                        <td><?= e(number_format((float)$participant['punctaj_obtinut'], 2, '.', '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    return trim(ob_get_clean());
}
