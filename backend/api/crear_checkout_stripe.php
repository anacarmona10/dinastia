<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/conexion.php';

function responder(int $codigo, array $datos): never {
    http_response_code($codigo);
    echo json_encode($datos);
    exit;
}

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

$stripeKey = getenv('STRIPE_SECRET_KEY');
$appUrl = rtrim((string) getenv('APP_URL'), '/');

if (!$stripeKey || !$appUrl) {
    responder(500, ['ok' => false, 'error' => 'Stripe no está configurado']);
}

try {
    $consulta = $pdo->prepare(
        'SELECT id, destino, precio FROM viajes WHERE id = :id LIMIT 1'
    );
    $consulta->execute(['id' => $viajeId]);
    $viaje = $consulta->fetch();

    if (!$viaje) {
        responder(404, ['ok' => false, 'error' => 'Viaje no encontrado']);
    }

    $precioUnitarioCentavos = (int) round(((float) $viaje['precio']) * 100);
    $totalCentavos = $precioUnitarioCentavos * $cantidad;
    $referencia = 'DIN-' . date('YmdHis') . '-' . bin2hex(random_bytes(5));

    $pdo->beginTransaction();

    $insertarPago = $pdo->prepare(
        'INSERT INTO pagos
        (referencia, usuario_id, viaje_id, cantidad_personas, monto_centavos, estado)
        VALUES (:referencia, :usuario_id, :viaje_id, :cantidad, :monto, :estado)
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

    $stripe = new \Stripe\StripeClient($stripeKey);

    $checkout = $stripe->checkout->sessions->create([
        'mode' => 'payment',
        'customer_email' => $_SESSION['user_email'],
        'success_url' => $appUrl . '/frontend/pago_exitoso.html?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $appUrl . '/frontend/pago_cancelado.html?pago=' . $pagoId,
        'metadata' => [
            'pago_id' => (string) $pagoId,
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

    responder(200, ['ok' => true, 'url' => $checkout->url]);

} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Error Stripe: ' . $error->getMessage());
    responder(500, ['ok' => false, 'error' => 'No fue posible iniciar el pago']);
}