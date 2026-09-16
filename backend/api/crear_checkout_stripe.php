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

$entrada = json_decode(file_get_contents('php://input'), true);
$viajeId = filter_var($entrada['viaje_id'] ?? null, FILTER_VALIDATE_INT);
$cantidad = filter_var($entrada['cantidad_personas'] ?? 1, FILTER_VALIDATE_INT);

if (!$viajeId || !$cantidad || $cantidad < 1 || $cantidad > 10) {
    responder(422, ['ok' => false, 'error' => 'Datos de reserva inválidos']);
}

if (!$stripeKey || !$appUrl) {
    responder(500, ['ok' => false, 'error' => 'Stripe no está configurado']);
}

try {
    // =========================================================
    // 1. Obtener datos del viaje
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

    // =========================================================
    // 2. Generar código único de reserva
    // =========================================================
    $anio = date('Y');
    $prefijo = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $viaje['destino']), 0, 3));
    if (strlen($prefijo) < 3) $prefijo = str_pad($prefijo, 3, 'X');

    $codigoReserva = '';
    $intentos = 0;
    do {
        $random = str_pad((string)random_int(0, 999), 3, '0', STR_PAD_LEFT);
        $codigoReserva = "AMV-{$anio}-{$prefijo}-{$random}";
        try {
            $check = $pdo->prepare('SELECT id FROM reservas WHERE codigo = ?');
            $check->execute([$codigoReserva]);
            $existe = $check->fetch();
        } catch (Throwable $e) {
            $existe = false;
        }
        $intentos++;
    } while ($existe && $intentos < 10);

    $referencia = 'DIN-' . date('YmdHis') . '-' . bin2hex(random_bytes(5));

    // =========================================================
    // 3. Crear reserva en la BD (antes de Stripe)
    // =========================================================
    $pdo->beginTransaction();

    $reservaId = null;
    try {
        $insertarReserva = $pdo->prepare(
            'INSERT INTO reservas
                (codigo, usuario_id, viaje_id, cantidad_personas, precio_unitario, precio_total,
                 estado_pago, metodo_pago, fecha_salida, fecha_regreso)
             VALUES
                (:codigo, :usuario_id, :viaje_id, :cantidad, :precio_unitario, :precio_total,
                 :estado, :metodo, :fecha_salida, :fecha_regreso)
             RETURNING id'
        );

        $insertarReserva->execute([
            'codigo' => $codigoReserva,
            'usuario_id' => $_SESSION['user_id'],
            'viaje_id' => $viajeId,
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'precio_total' => $precioTotal,
            'estado' => 'pendiente de pago',
            'metodo' => 'Stripe',
            'fecha_salida' => $viaje['fecha_salida'] ?? null,
            'fecha_regreso' => $viaje['fecha_regreso'] ?? null,
        ]);

        $reservaId = (int) $insertarReserva->fetchColumn();
    } catch (Throwable $e) {
        // Si la tabla reservas no existe o hay un error, continuamos sin reserva
        error_log('Error al insertar reserva: ' . $e->getMessage());
        $reservaId = null;
    }

    // =========================================================
    // 4. Crear pago en la BD
    // =========================================================
    $insertarPago = $pdo->prepare(
        'INSERT INTO pagos
            (referencia, usuario_id, viaje_id, cantidad_personas, monto_centavos, estado)
         VALUES 
            (:referencia, :usuario_id, :viaje_id, :cantidad, :monto, :estado)
         RETURNING id'
    );

    $insertarPago->execute([
        'referencia' => $referencia,
        'usuario_id' => $_SESSION['user_id'],
        'viaje_id' => $viajeId,
        'cantidad' => $cantidad,
        'monto' => $totalCentavos,
        'estado' => 'PENDING',
    ]);

    $pagoId = (int) $insertarPago->fetchColumn();

    // Enlazar reserva con pago (opcional, no falla si la columna no existe)
    if ($reservaId) {
        try {
            $link = $pdo->prepare('UPDATE reservas SET pago_id = :pago_id WHERE id = :id');
            $link->execute(['pago_id' => $pagoId, 'id' => $reservaId]);
        } catch (Throwable $e) {
            // Ignorar si no existe la columna pago_id
        }
    }

    // =========================================================
    // 5. Crear sesión de Stripe
    // =========================================================
    $stripe = new \Stripe\StripeClient($stripeKey);

    $successUrl = $appUrl . '/frontend/pago_exitoso.html?session_id={CHECKOUT_SESSION_ID}';
    if ($reservaId) {
        $successUrl .= '&reserva=' . $reservaId;
    }

    $checkout = $stripe->checkout->sessions->create([
        'mode' => 'payment',
        'customer_email' => $_SESSION['user_email'],
        'success_url' => $successUrl,
        'cancel_url' => $appUrl . '/frontend/pago_cancelado.html?pago=' . $pagoId,
        'metadata' => [
            'pago_id' => (string) $pagoId,
            'reserva_id' => $reservaId ? (string) $reservaId : '',
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

    // Guardar el session_id en el pago
    $actualizarPago = $pdo->prepare(
        'UPDATE pagos
         SET stripe_checkout_session_id = :session_id, updated_at = NOW()
         WHERE id = :id'
    );

    $actualizarPago->execute([
        'session_id' => $checkout->id,
        'id' => $pagoId,
    ]);

    $pdo->commit();

    responder(200, [
        'ok' => true,
        'url' => $checkout->url,
        'reserva' => $reservaId ? [
            'id' => $reservaId,
            'codigo' => $codigoReserva,
        ] : null,
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