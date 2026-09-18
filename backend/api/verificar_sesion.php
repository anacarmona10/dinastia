<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!empty($_SESSION['logged_in'])) {
    $tipo = $_SESSION['tipo_usuario'] ?? 'usuario';
    echo json_encode([
        'autenticado' => true,
        'tipo' => $tipo,
        'nombre' => $tipo === 'admin' ? ($_SESSION['admin_nombre'] ?? 'Administrador') : ($_SESSION['user_nombre'] ?? 'Usuario'),
        'email' => $tipo === 'admin' ? ($_SESSION['admin_email'] ?? '') : ($_SESSION['user_email'] ?? '')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'autenticado' => false
], JSON_UNESCAPED_UNICODE);
exit;
