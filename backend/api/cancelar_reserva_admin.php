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

    // Consultar existencia de la reserva
    $stmt = $pdo->prepare('SELECT id, usuario_id, viaje_id, estado, referencia FROM reservas WHERE id = ?');
    $stmt->execute([$id]);
    $reserva = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fallback: si no se encontró por ID de reserva, buscar en pagos
    if (!$reserva) {
        $stmtP = $pdo->prepare('SELECT id, usuario_id, viaje_id, estado, referencia, reserva_id FROM pagos WHERE id = ?');
        $stmtP->execute([$id]);
        $pagoRow = $stmtP->fetch(PDO::FETCH_ASSOC);
        if ($pagoRow && !empty($pagoRow['reserva_id'])) {
            $stmt->execute([$pagoRow['reserva_id']]);
            $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        if (!$reserva && $pagoRow) {
            $reserva = $pagoRow;
        }
    }

    if (!$reserva) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'La reserva especificada no existe'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (strtoupper((string)$reserva['estado']) === 'CANCELLED' || strtoupper((string)$reserva['estado']) === 'CANCELADO') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Esta reserva ya se encuentra cancelada'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdo->beginTransaction();

    $reservaId = (int)$reserva['id'];

    // Actualizar estado en reservas
    $stmtUpRes = $pdo->prepare("UPDATE reservas SET estado = 'CANCELLED', updated_at = NOW() WHERE id = ?");
    $stmtUpRes->execute([$reservaId]);

    // Actualizar también en pagos vinculados
    $stmtUpPago = $pdo->prepare("UPDATE pagos SET estado = 'CANCELLED', updated_at = NOW() WHERE reserva_id = ? OR id = ?");
    $stmtUpPago->execute([$reservaId, $reservaId]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'mensaje' => 'Reserva ' . ($reserva['referencia'] ?? ('#' . $reservaId)) . ' cancelada correctamente'
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
