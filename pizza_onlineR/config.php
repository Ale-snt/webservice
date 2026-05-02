<?php
$host   = 'localhost';
$dbname = 'pizza_online';
$user   = 'root';
$pass   = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Errore database: " . $e->getMessage());
}

session_start();
?>
