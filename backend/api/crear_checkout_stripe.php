<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/conexion.php';

function responder(int $codigo, array $datos): never {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

$stripeKey = variableEntorno('STRIPE_SECRET_KEY');
$appUrl = rtrim(variableEntorno('APP_URL'), '/');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, ['ok' => false, 'error' => 'Método no permitido']);
}

if (empty($_SESSION['logged_in']) || ($_SESSION['tipo_usuario'] ?? '') !== 'usuario') {
    responder(401, ['ok' => false, 'error' => 'Debes iniciar sesión para pagar']);
}

$entrada = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$reservaId = filter_var($entrada['reserva_id'] ?? null, FILTER_VALIDATE_INT);
$viajeId = filter_var($entrada['viaje_id'] ?? null, FILTER_VALIDATE_INT);
$cantidad = filter_var($entrada['cantidad_personas'] ?? 1, FILTER_VALIDATE_INT);

if (!$stripeKey || !$appUrl) {
    responder(500, ['ok' => false, 'error' => 'Stripe no está configurado']);
}

try {
    $referencia = '';
    $codigoReserva = '';

    // =========================================================
    // 1. Si se envía reserva_id, cargar datos de la reserva existente
    // =========================================================
    if ($reservaId) {
        $stmtRes = $pdo->prepare('SELECT id, usuario_id, viaje_id, cantidad_personas, monto_centavos, estado, referencia FROM reservas WHERE id = :id AND usuario_id = :uid LIMIT 1');
        $stmtRes->execute(['id' => $reservaId, 'uid' => $_SESSION['user_id']]);
        $reservaExistente = $stmtRes->fetch();

        if (!$reservaExistente) {
            responder(404, ['ok' => false, 'error' => 'Reserva no encontrada']);
        }

        if (in_array(strtoupper($reservaExistente['estado']), ['APPROVED', 'PAGADO', 'PAGADA', 'PAGADO CON ÉXITO'])) {
            responder(400, ['ok' => false, 'error' => 'Esta reserva ya se encuentra pagada']);
        }

        $viajeId = (int) $reservaExistente['viaje_id'];
        $cantidad = (int) $reservaExistente['cantidad_personas'];
        $referencia = $reservaExistente['referencia'] ?? '';
        $codigoReserva = $referencia;
    }

    if (!$viajeId || !$cantidad || $cantidad < 1 || $cantidad > 10) {
        responder(422, ['ok' => false, 'error' => 'Datos de reserva inválidos']);
    }

    // =========================================================
    // 2. Obtener datos del viaje
    // =========================================================
    $consulta = $pdo->prepare(
        'SELECT id, destino, precio, fecha_salida, fecha_regreso 
         FROM viajes WHERE id = :id LIMIT 1'
    );
    $consulta->execute(['id' => $viajeId]);
    $viaje = $consulta->fetch();

    if (!$viaje) {
        responder(404, ['ok' => false, 'error' => 'Viaje no encontrado']);
    }

    $precioUnitario = (float) $viaje['precio'];
    $precioUnitarioCentavos = (int) round($precioUnitario * 100);
    $totalCentavos = $precioUnitarioCentavos * $cantidad;
    $precioTotal = $precioUnitario * $cantidad;

    // Si no había referencia previa, generarla
    if ($referencia === '') {
        $anio = date('Y');
        $prefijo = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $viaje['destino']), 0, 3));
        if (strlen($prefijo) < 3) $prefijo = str_pad($prefijo, 3, 'X');

        $intentos = 0;
        do {
            $random = str_pad((string)random_int(0, 999), 3, '0', STR_PAD_LEFT);
            $referencia = "AMV-{$anio}-{$prefijo}-{$random}";
            $check = $pdo->prepare('SELECT id FROM reservas WHERE referencia = ? LIMIT 1');
            $check->execute([$referencia]);
            $existe = $check->fetch();
            $intentos++;
        } while ($existe && $intentos < 10);
        $codigoReserva = $referencia;
    }

    $pdo->beginTransaction();

    // =========================================================
    // 3. Crear o actualizar la reserva en la tabla reservas
    // =========================================================
    if (!$reservaId) {
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
            'monto_centavos' => $totalCentavos,
            'metodo_pago' => 'Stripe'
        ]);

        $reservaId = (int) $insertarReserva->fetchColumn();
    }

    // =========================================================
    // 4. Crear pago en la BD vinculado a la reserva (reserva_id)
    // =========================================================
    $insertarPago = $pdo->prepare(
        'INSERT INTO pagos
            (referencia, usuario_id, viaje_id, cantidad_personas, monto_centavos, estado, reserva_id, created_at, updated_at)
         VALUES 
            (:referencia, :usuario_id, :viaje_id, :cantidad, :monto, :estado, :reserva_id, NOW(), NOW())
         RETURNING id'
    );

    $insertarPago->execute([
        'referencia' => $referencia,
        'usuario_id' => $_SESSION['user_id'],
        'viaje_id' => $viajeId,
        'cantidad' => $cantidad,
        'monto' => $totalCentavos,
        'estado' => 'PENDING',
        'reserva_id' => $reservaId
    ]);

    $pagoId = (int) $insertarPago->fetchColumn();

    // =========================================================
    // 5. Crear sesión de Stripe
    // =========================================================
    $stripe = new \Stripe\StripeClient($stripeKey);

    $successUrl = $appUrl . '/frontend/pago_exitoso.html?session_id={CHECKOUT_SESSION_ID}&reserva=' . $reservaId;

    $checkout = $stripe->checkout->sessions->create([
        'mode' => 'payment',
        'customer_email' => $_SESSION['user_email'],
        'success_url' => $successUrl,
        'cancel_url' => $appUrl . '/frontend/pago_cancelado.html?pago=' . $pagoId . '&reserva=' . $reservaId,
        'metadata' => [
            'pago_id' => (string) $pagoId,
            'reserva_id' => (string) $reservaId,
            'referencia' => $referencia,
            'viaje_id' => (string) $viajeId,
        ],
        'line_items' => [[
            'quantity' => $cantidad,
            'price_data' => [
                'currency' => 'cop',
                'unit_amount' => $precioUnitarioCentavos,
                'product_data' => [
                    'name' => 'Plan turístico: ' . $viaje['destino'],
                ],
            ],
        ]],
    ]);

    // Guardar el session_id tanto en pagos como en reservas
    $actualizarPago = $pdo->prepare(
        'UPDATE pagos
         SET stripe_checkout_session_id = :session_id, updated_at = NOW()
         WHERE id = :id'
    );
    $actualizarPago->execute([
        'session_id' => $checkout->id,
        'id' => $pagoId,
    ]);

    $actualizarReserva = $pdo->prepare(
        'UPDATE reservas
         SET stripe_checkout_session_id = :session_id, updated_at = NOW()
         WHERE id = :id'
    );
    $actualizarReserva->execute([
        'session_id' => $checkout->id,
        'id' => $reservaId,
    ]);

    $pdo->commit();

    responder(200, [
        'ok' => true,
        'url' => $checkout->url,
        'reserva' => [
            'id' => $reservaId,
            'codigo' => $codigoReserva,
            'referencia' => $referencia
        ],
        'pago_id' => $pagoId
    ]);

} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Error Stripe: ' . $error->getMessage());
    responder(500, [
        'ok' => false, 
        'error' => 'No fue posible iniciar el pago',
        'detalle' => $error->getMessage()
    ]);
}