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
    $id = $_POST['id'] ?? null;
    if ($id === null) {
        $rawInput = file_get_contents('php://input');
        if (!empty($rawInput)) {
            $jsonData = json_decode($rawInput, true);
            $id = $jsonData['id'] ?? null;
        }
    }

    if (empty($id) || !is_numeric($id) || (int)$id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'ID de reserva no válido'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $id = (int)$id;

    // Consultar existencia del pago / reserva
    $stmt = $pdo->prepare('SELECT id, usuario_id, viaje_id, estado, referencia FROM pagos WHERE id = ?');
    $stmt->execute([$id]);
    $pago = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pago) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'La reserva especificada no existe'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($pago['estado'] === 'CANCELLED') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Esta reserva ya se encuentra cancelada'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo->beginTransaction();

    // Actualizar estado en pagos
    $stmtUpPago = $pdo->prepare("UPDATE pagos SET estado = 'CANCELLED', updated_at = NOW() WHERE id = ?");
    $stmtUpPago->execute([$id]);

    // Actualizar también en reservas si existe coincidencia de usuario y viaje
    if (!empty($pago['usuario_id']) && !empty($pago['viaje_id'])) {
        $stmtUpRes = $pdo->prepare("UPDATE reservas SET estado = 'cancelado' WHERE user_id = ? AND viaje_id = ?");
        $stmtUpRes->execute([$pago['usuario_id'], $pago['viaje_id']]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'mensaje' => 'Reserva ' . $pago['referencia'] . ' cancelada correctamente'
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error en base de datos: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
