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
    responder(401, ['ok' => false, 'error' => 'Debes iniciar sesión para realizar una reserva']);
}

$entrada = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$viajeId = filter_var($entrada['viaje_id'] ?? null, FILTER_VALIDATE_INT);
$cantidad = filter_var($entrada['cantidad_personas'] ?? 1, FILTER_VALIDATE_INT);
$metodoPago = trim((string)($entrada['metodo_pago'] ?? 'Pagar después'));
if ($metodoPago === '') {
    $metodoPago = 'Pagar después';
}

if (!$viajeId || !$cantidad || $cantidad < 1 || $cantidad > 10) {
    responder(422, ['ok' => false, 'error' => 'Datos de reserva inválidos']);
}

try {
    // 1. Obtener viaje y validar existencia
    $consulta = $pdo->prepare('SELECT id, destino, precio, fecha_salida, fecha_regreso FROM viajes WHERE id = :id LIMIT 1');
    $consulta->execute(['id' => $viajeId]);
    $viaje = $consulta->fetch();

    if (!$viaje) {
        responder(404, ['ok' => false, 'error' => 'Viaje no encontrado']);
    }

    $precioUnitario = (float) $viaje['precio'];
    $montoCentavos = (int) round($precioUnitario * 100) * $cantidad;
    $precioTotal = $precioUnitario * $cantidad;

    // 2. Generar código único de reserva (formato AMV-YYYY-PREFIX-XXX)
    $anio = date('Y');
    $prefijo = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $viaje['destino']), 0, 3));
    if (strlen($prefijo) < 3) {
        $prefijo = str_pad($prefijo, 3, 'X');
    }

    $referencia = '';
    $intentos = 0;
    do {
        $random = str_pad((string)random_int(0, 999), 3, '0', STR_PAD_LEFT);
        $referencia = "AMV-{$anio}-{$prefijo}-{$random}";
        $check = $pdo->prepare('SELECT id FROM reservas WHERE referencia = ? LIMIT 1');
        $check->execute([$referencia]);
        $existe = $check->fetch();
        $intentos++;
    } while ($existe && $intentos < 10);

    // 3. Insertar reserva exclusivamente en la tabla reservas (NO en pagos)
    $insertarReserva = $pdo->prepare(
        'INSERT INTO reservas
            (usuario_id, viaje_id, estado, fecha_reserva, referencia, cantidad_personas, monto_centavos, metodo_pago, created_at, updated_at)
         VALUES
            (:usuario_id, :viaje_id, :estado, NOW(), :referencia, :cantidad, :monto_centavos, :metodo_pago, NOW(), NOW())
         RETURNING id'
    );

    $insertarReserva->execute([
        'usuario_id' => $_SESSION['user_id'],
        'viaje_id' => $viajeId,
        'estado' => 'PENDING',
        'referencia' => $referencia,
        'cantidad' => $cantidad,
        'monto_centavos' => $montoCentavos,
        'metodo_pago' => $metodoPago
    ]);

    $reservaId = (int) $insertarReserva->fetchColumn();

    responder(201, [
        'ok' => true,
        'mensaje' => '¡Reserva realizada con éxito! Tu cupo está reservado y tu pago se encuentra pendiente.',
        'reserva' => [
            'id' => $reservaId,
            'referencia' => $referencia,
            'destino' => $viaje['destino'],
            'cantidad_personas' => $cantidad,
            'monto_centavos' => $montoCentavos,
            'total_cop' => $precioTotal,
            'estado' => 'PENDING',
            'metodo_pago' => $metodoPago,
            'fecha_salida' => $viaje['fecha_salida'],
            'fecha_regreso' => $viaje['fecha_regreso']
        ]
    ]);

} catch (Throwable $e) {
    error_log('Error crear_reserva: ' . $e->getMessage());
    responder(500, [
        'ok' => false,
        'error' => 'No fue posible completar la reserva: ' . $e->getMessage()
    ]);
}
