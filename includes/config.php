<?php
date_default_timezone_set('Europe/Bucharest');

$host = 'localhost';
$dbname = 'esc_chess_club';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, //interog sql
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, ///cerem date 
        ]
    );

    require_once __DIR__ . '/schema.php';
    ensure_schema($pdo);
} catch (PDOException $e) {
    die("Eroare la conectarea cu baza de date: " . $e->getMessage());
}
