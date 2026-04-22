<?php
$host = 'localhost';
$dbname = 'esc_chess_club';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Eroare la conectarea cu baza de date: " . $e->getMessage());
}
?>