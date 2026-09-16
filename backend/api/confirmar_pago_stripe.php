<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

function responder(int $codigo, array $datos): never {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, ['ok' => false, 'error' => 'Método no permitido']);
}

if (empty($_SESSION['logged_in']) || ($_SESSION['tipo_usuario'] ?? '') !== 'usuario') {
    responder(401, ['ok' => false, 'error' => 'Debes iniciar sesión']);
}

$reservaId = filter_var($_POST['reserva_id'] ?? null, FILTER_VALIDATE_INT);
$sessionId = trim($_POST['session_id'] ?? '');
$usuarioId = $_SESSION['user_id'];

if (!$reservaId) {
    responder(422, ['ok' => false, 'error' => 'ID de reserva inválido']);
}

try {
    // Verificar que la reserva exista y sea del usuario
    $stmt = $pdo->prepare('SELECT id, codigo, estado_pago FROM reservas WHERE id = :id AND usuario_id = :usuario');
    $stmt->execute(['id' => $reservaId, 'usuario' => $usuarioId]);
    $reserva = $stmt->fetch();

    if (!$reserva) {
        responder(404, ['ok' => false, 'error' => 'Reserva no encontrada']);
    }

    // Actualizar la reserva: estado pagado + método "Pagado con éxito"
    $sql = 'UPDATE reservas 
            SET estado_pago = :estado, 
                metodo_pago = :metodo, 
                fecha_pago = NOW()';

    $params = [
        'estado' => 'pagado',
        'metodo' => 'Pagado con éxito',
        'id' => $reservaId
    ];

    try {
        $sql .= ', stripe_session_id = :session_id';
        $params['session_id'] = $sessionId;
        $sql .= ' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } catch (Throwable $e) {
        // Si no existe la columna stripe_session_id
        $sql = 'UPDATE reservas 
                SET estado_pago = :estado, 
                    metodo_pago = :metodo, 
                    fecha_pago = NOW()
                WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'estado' => 'pagado',
            'metodo' => 'Pagado con éxito',
            'id' => $reservaId
        ]);
    }

    // Actualizar la tabla pagos si existe
    try {
        $stmtPago = $pdo->prepare('UPDATE pagos SET estado = :estado WHERE id = (SELECT pago_id FROM reservas WHERE id = :rid)');
        $stmtPago->execute(['estado' => 'PAID', 'rid' => $reservaId]);
    } catch (Throwable $e) {
        // Ignorar si no se puede actualizar pagos
    }

    responder(200, [
        'ok' => true,
        'mensaje' => 'Pago confirmado correctamente',
        'codigo' => $reserva['codigo'],
        'reserva_id' => $reservaId
    ]);

} catch (Throwable $error) {
    error_log('Error confirmar pago: ' . $error->getMessage());
    responder(500, ['ok' => false, 'error' => 'No fue posible confirmar el pago: ' . $error->getMessage()]);
}