<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/enviar_correo.php';

function variableEntorno(string $nombre): string
{
    return $_ENV[$nombre] ?? $_SERVER[$nombre] ?? getenv($nombre) ?: '';
}

$payload = file_get_contents('php://input');
$firma = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$secretoWebhook = variableEntorno('STRIPE_WEBHOOK_SECRET');

if ($secretoWebhook === '') {
    http_response_code(500);
    exit;
}

try {
    $evento = \Stripe\Webhook::constructEvent(
        $payload,
        $firma,
        $secretoWebhook
    );
} catch (Throwable $error) {
    http_response_code(400);
    exit;
}

$pagoConfirmado = null;

try {
    $pdo->beginTransaction();

    $registrarEvento = $pdo->prepare(
        'INSERT INTO stripe_eventos (stripe_event_id, tipo)
         VALUES (:id, :tipo)
         ON CONFLICT (stripe_event_id) DO NOTHING'
    );

    $registrarEvento->execute([
        'id' => $evento->id,
        'tipo' => $evento->type,
    ]);

    if ($registrarEvento->rowCount() === 0) {
        $pdo->commit();
        http_response_code(200);
        exit;
    }

    $checkout = $evento->data->object;
    $estado = null;

    if (
        $evento->type === 'checkout.session.completed' &&
        $checkout->payment_status === 'paid'
    ) {
        $estado = 'APPROVED';
    }

    if ($evento->type === 'checkout.session.async_payment_succeeded') {
        $estado = 'APPROVED';
    }

    if ($evento->type === 'checkout.session.async_payment_failed') {
        $estado = 'FAILED';
    }

    if ($evento->type === 'checkout.session.expired') {
        $estado = 'EXPIRED';
    }

    if ($estado !== null) {
        $actualizarPago = $pdo->prepare(
            'UPDATE pagos AS p
             SET estado = :estado,
                 stripe_payment_intent_id = :payment_intent,
                 updated_at = NOW()
             FROM usuarios AS u, viajes AS v
             WHERE p.stripe_checkout_session_id = :session_id
               AND p.usuario_id = u.id
               AND p.viaje_id = v.id
               AND (
                   :estado <> \'APPROVED\'
                   OR p.estado IS DISTINCT FROM \'APPROVED\'
               )
             RETURNING
                p.referencia,
                p.cantidad_personas,
                p.monto_centavos,
                u.correo,
                u."nombreCompleto" AS nombre,
                v.destino'
        );

        $actualizarPago->execute([
            'estado' => $estado,
            'payment_intent' => is_string($checkout->payment_intent ?? null)
                ? $checkout->payment_intent
                : null,
            'session_id' => $checkout->id,
        ]);

        $pagoConfirmado = $actualizarPago->fetch();
    }

    $pdo->commit();

    /*
     * Se envía solo tras confirmar y guardar el pago.
     * El control de estado evita que distintos eventos
     * de Stripe generen correos duplicados.
     */
    if (
        $estado === 'APPROVED' &&
        is_array($pagoConfirmado)
    ) {
        enviarCorreoConfirmacionPago($pagoConfirmado);
    }

    http_response_code(200);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Error webhook Stripe: ' . $error->getMessage());
    http_response_code(500);
}