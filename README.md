<?php
$host = 'localhost';
$db = 'c2651511_distri';
$user = 'c2651511_distri';
$pass = 'marowe35LO';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?> 


