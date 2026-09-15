<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/validaciones.php';

if (!puedeGestionarViajes($_SESSION)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Debes iniciar sesión como administrador'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $sql = 'SELECT 
        u.id,
        u."nombreCompleto" AS nombre,
        u."tipoDocumento" AS tipo_documento,
        u."numeroDocumento" AS numero_documento,
        u.correo,
        u.created_at,
        (SELECT COUNT(*) FROM pagos WHERE usuario_id = u.id) AS total_reservas
    FROM usuarios AS u
    ORDER BY u.id DESC';

    $stmt = $pdo->query($sql);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'usuarios' => $usuarios
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al consultar usuarios: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
