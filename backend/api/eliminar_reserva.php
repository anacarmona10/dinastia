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
$usuarioId = $_SESSION['user_id'];

if (!$reservaId) {
    responder(422, ['ok' => false, 'error' => 'ID de reserva inválido']);
}

try {
    // 1. Verificar que la reserva exista y sea del usuario
    $stmt = $pdo->prepare('SELECT id FROM reservas WHERE id = :id AND usuario_id = :usuario LIMIT 1');
    $stmt->execute(['id' => $reservaId, 'usuario' => $usuarioId]);
    $existe = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existe) {
        responder(404, ['ok' => false, 'error' => 'Reserva no encontrada o no te pertenece']);
    }

    // 2. Eliminar la reserva (sin transacción, sin tocar otras tablas)
    $stmtDel = $pdo->prepare('DELETE FROM reservas WHERE id = :id AND usuario_id = :usuario');
    $stmtDel->execute(['id' => $reservaId, 'usuario' => $usuarioId]);

    $filasAfectadas = $stmtDel->rowCount();

    if ($filasAfectadas === 0) {
        responder(500, ['ok' => false, 'error' => 'No se pudo eliminar la reserva']);
    }

    responder(200, [
        'ok' => true,
        'mensaje' => 'Reserva cancelada correctamente',
        'filas' => $filasAfectadas
    ]);

} catch (Throwable $error) {
    error_log('Error al eliminar reserva: ' . $error->getMessage());
    responder(500, [
        'ok' => false,
        'error' => 'No fue posible cancelar la reserva: ' . $error->getMessage()
    ]);
}