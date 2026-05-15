<?php
require_once 'includes/auth_check.php';
require_once 'includes/config.php';
require_once 'includes/functions.php';

$pageTitle = 'eSC - Mentenanta';
require 'includes/header.php';
?>
<section class="page-title">
    <h2>Mentenanta proiect</h2>
    <p>Fisierul vechi de curatare automata a fost scos din fluxul activ pentru a evita modificari accidentale ale codului.</p>
</section>

<section class="panel">
    <h3>Structura activa</h3>
    <p>Layout-ul comun se afla in `includes/header.php` si `includes/footer.php`, iar stilurile active sunt in `assets/css/style.css`.</p>
    <div class="action-list">
        <a class="button secondary" href="index.php">Inapoi la panou</a>
        <a class="button secondary" href="README.md">README</a>
    </div>
</section>
<?php require 'includes/footer.php'; ?>
