<?php
$host = 'mysql-gestionetude.alwaysdata.net';
$dbname = 'gestionetude_plateforme_db';
$username = 'gestionetude';
$password = 'oussou32';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>