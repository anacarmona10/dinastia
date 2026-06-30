<?php
$host = 'localhost';
$dbname = 'agencia_amv';    // el nombre que de la BD
$user = 'root';
$password = '';              // por defecto en XAMPP es vacío

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Si quieres verificar que funciona (solo para pruebas):
    //echo "Conexión exitosa";
} catch(PDOException $e) {
    // En producción no muestres el mensaje interno, pero para desarrollo está bien
    die("Error de conexión: " . $e->getMessage());
}
?>