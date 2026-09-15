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
        p.id,
        p.referencia,
        p.cantidad_personas,
        p.monto_centavos,
        p.estado,
        p.created_at,
        u.id AS usuario_id,
        u."nombreCompleto" AS usuario_nombre,
        u.correo AS usuario_correo,
        u."tipoDocumento" AS usuario_tipo_doc,
        u."numeroDocumento" AS usuario_documento,
        v.id AS viaje_id,
        v.destino,
        v.fecha_salida,
        v.fecha_regreso
    FROM pagos AS p
    INNER JOIN usuarios AS u ON u.id = p.usuario_id
    INNER JOIN viajes AS v ON v.id = p.viaje_id
    ORDER BY p.id DESC';

    $stmt = $pdo->query($sql);
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'reservas' => $reservas
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al consultar reservas: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
