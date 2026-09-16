<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
$registro = $_SESSION['registro_pendiente'] ?? null;
if (!is_array($registro) || empty($registro['correo'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No hay un registro pendiente.'], JSON_UNESCAPED_UNICODE);
    exit;
}
echo json_encode(['success' => true, 'correo' => $registro['correo']], JSON_UNESCAPED_UNICODE);
