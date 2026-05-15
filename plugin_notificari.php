<?php
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SERVER['HTTP_HOST'])) {
    $pluginStatsUrl = app_base_url() . '/api/stats.php';
    $pluginContext = stream_context_create([
        'http' => [
            'timeout' => 1.5,
        ],
    ]);
    $pluginResponse = @file_get_contents($pluginStatsUrl, false, $pluginContext);
    $pluginStats = $pluginResponse ? json_decode($pluginResponse, true) : null;

    if (is_array($pluginStats) && !empty($pluginStats['competition_today'])) {
        $pluginCompetitionNames = [];

        foreach (($pluginStats['today_competitions'] ?? []) as $pluginCompetition) {
            if (!empty($pluginCompetition['nume'])) {
                $pluginCompetitionNames[] = $pluginCompetition['nume'];
            }
        }
        ?>
        <div class="toast" role="status" aria-live="polite">
            <strong>Competitie astazi</strong>
            <span><?= e($pluginCompetitionNames ? implode(', ', $pluginCompetitionNames) : 'Verifica programul competitiilor.') ?></span>
        </div>
        <?php
    }
}
