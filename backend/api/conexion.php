<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Cargar las variables del archivo .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Obtener las credenciales
$host = $_ENV['DB_HOST'];
$endpoint = $_ENV['DB_ENDPOINT'];
$dbname = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$password = $_ENV['DB_PASSWORD'];

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=5432;dbname=$dbname;sslmode=require;options=endpoint=$endpoint",
        $user,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>