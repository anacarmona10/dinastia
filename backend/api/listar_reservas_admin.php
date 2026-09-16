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
        r.id,
        r.referencia,
        r.cantidad_personas,
        r.monto_centavos,
        r.estado,
        r.metodo_pago,
        r.fecha_reserva,
        r.fecha_pago,
        r.created_at,
        u.id AS usuario_id,
        u."nombreCompleto" AS usuario_nombre,
        u.correo AS usuario_correo,
        u."tipoDocumento" AS usuario_tipo_doc,
        u."numeroDocumento" AS usuario_documento,
        v.id AS viaje_id,
        v.destino,
        v.fecha_salida,
        v.fecha_regreso,
        p.id AS pago_id,
        p.estado AS pago_estado
    FROM reservas AS r
    INNER JOIN usuarios AS u ON u.id = r.usuario_id
    INNER JOIN viajes AS v ON v.id = r.viaje_id
    LEFT JOIN pagos AS p ON p.reserva_id = r.id
    ORDER BY r.id DESC';

    $stmt = $pdo->query($sql);
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($reservas as &$res) {
        $estadoRaw = strtoupper(trim((string)($res['estado'] ?? '')));
        $pagoEstadoRaw = strtoupper(trim((string)($res['pago_estado'] ?? '')));

        if (in_array($estadoRaw, ['APPROVED', 'PAGADO', 'PAGADA', 'PAGADO CON ÉXITO']) || $pagoEstadoRaw === 'APPROVED') {
            $res['estado'] = 'APPROVED';
        } elseif (in_array($estadoRaw, ['CANCELLED', 'CANCELADO', 'CANCELADA', 'FAILED', 'EXPIRED']) || in_array($pagoEstadoRaw, ['CANCELLED', 'FAILED', 'EXPIRED'])) {
            $res['estado'] = 'CANCELLED';
        } else {
            $res['estado'] = 'PENDING';
        }
    }
    unset($res);

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
