<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/conexion.php';

$payload = file_get_contents('php://input');
$firma = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$secretoWebhook = getenv('STRIPE_WEBHOOK_SECRET');

if (!$secretoWebhook) {
    http_response_code(500);
    exit;
}

try {
    $evento = \Stripe\Webhook::constructEvent($payload, $firma, $secretoWebhook);
} catch (Throwable $error) {
    http_response_code(400);
    exit;
}

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
            'UPDATE pagos
             SET estado = :estado,
                 stripe_payment_intent_id = :payment_intent,
                 updated_at = NOW()
             WHERE stripe_checkout_session_id = :session_id'
        );

        $actualizarPago->execute([
            'estado' => $estado,
            'payment_intent' => is_string($checkout->payment_intent ?? null)
                ? $checkout->payment_intent
                : null,
            'session_id' => $checkout->id,
        ]);
    }

    $pdo->commit();
    http_response_code(200);

} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($error->getMessage());
    http_response_code(500);
}