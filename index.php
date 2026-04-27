<?php
require_once 'auth_check.php';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eSC - Chess Club Manager</title>
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
        <a href="logout.php">Logout (<?= htmlspecialchars($_SESSION['username']) ?>)</a>
    </nav>
    
    <main>
        <h2>Bine ai venit in aplicatia de gestiune a clubului de sah!</h2>
        <p>Alege o optiune din meniu pentru a incepe.</p>
    </main>

    <footer>
        <p>&copy; 2026 eSC Chess Club Manager. Toate drepturile rezervate.</p>
    </footer>
</body>
</html>