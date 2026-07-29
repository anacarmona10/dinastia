<?php
$host = 'ep-damp-rain-acyqtue1-pooler.sa-east-1.aws.neon.tech';
$dbname = 'neondb';
$user = 'neondb_owner';
$password = 'npg_ruLmj4Nbg2Bc';


try {
    $pdo = new PDO("pgsql:host=$host;port=5432;dbname=$dbname;sslmode=require", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>