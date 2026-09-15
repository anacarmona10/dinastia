<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/validaciones.php';

if (!puedeGestionarViajes($_SESSION)) {
    http_response_code(403);
    echo json_encode([
        'autenticado' => false,
        'error' => 'Debes iniciar sesión como administrador'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'autenticado' => true,
    'admin' => [
        'id' => $_SESSION['admin_id'] ?? null,
        'nombre' => $_SESSION['admin_nombre'] ?? 'Administrador',
        'email' => $_SESSION['admin_email'] ?? ''
    ]
], JSON_UNESCAPED_UNICODE);
