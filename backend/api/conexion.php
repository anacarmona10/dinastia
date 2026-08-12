<?php

$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME');
$user = getenv('DB_USER');
$password = getenv('DB_PASSWORD');
$sslmode = getenv('DB_SSLMODE') ?: 'require';

if (!$host || !$dbname || !$user || !$password) {
    http_response_code(500);
    die('Faltan las variables de conexión de base de datos.');
}

try {
    $pdo = new PDO(
        "pgsql:host={$host};port={$port};dbname={$dbname};sslmode={$sslmode};channel_binding=require",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    error_log('Error de conexión a Neon: ' . $e->getMessage());
    http_response_code(500);
    die('No fue posible conectar a la base de datos.');
}
?>
