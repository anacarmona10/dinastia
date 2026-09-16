<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/enviar_correo.php';

function responder(int $codigo, array $datos): never {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    responder(405, ['ok' => false, 'error' => 'Método no permitido']);
}

$entrada = json_decode(file_get_contents('php://input'), true) ?? [];
$sessionId = trim((string)($entrada['session_id'] ?? $_POST['session_id'] ?? $_GET['session_id'] ?? ''));
$reservaId = filter_var($entrada['reserva_id'] ?? $_POST['reserva_id'] ?? $_GET['reserva_id'] ?? null, FILTER_VALIDATE_INT) ?: null;

if ($sessionId === '' && !$reservaId) {
    responder(422, ['ok' => false, 'error' => 'Se requiere session_id o reserva_id']);
}

$stripeKey = variableEntorno('STRIPE_SECRET_KEY');

try {
    $paymentIntentId = null;
    $pagoId = null;
    $referencia = null;

    // 1. Si hay sessionId y tenemos la clave de Stripe, consultar a Stripe directamente
    if ($sessionId !== '' && $stripeKey !== '') {
        $stripe = new \Stripe\StripeClient($stripeKey);
        $checkout = $stripe->checkout->sessions->retrieve($sessionId);

        if ($checkout->payment_status !== 'paid') {
            responder(400, [
                'ok' => false, 
                'error' => 'El pago aún no ha sido completado en Stripe',
                'payment_status' => $checkout->payment_status
            ]);
        }

        $paymentIntentId = is_string($checkout->payment_intent ?? null) ? $checkout->payment_intent : null;

        if (!$reservaId && !empty($checkout->metadata->reserva_id)) {
            $reservaId = (int) $checkout->metadata->reserva_id;
        }
        if (!empty($checkout->metadata->pago_id)) {
            $pagoId = (int) $checkout->metadata->pago_id;
        }
        if (!empty($checkout->metadata->referencia)) {
            $referencia = (string) $checkout->metadata->referencia;
        }
    }

    $pdo->beginTransaction();

    // 2. Si aún no tenemos reservaId, buscarlo por sessionId
    if (!$reservaId && $sessionId !== '') {
        $stmtSearch = $pdo->prepare('SELECT id, referencia FROM reservas WHERE stripe_checkout_session_id = ? LIMIT 1');
        $stmtSearch->execute([$sessionId]);
        $row = $stmtSearch->fetch();
        if ($row) {
            $reservaId = (int) $row['id'];
            if (!$referencia) $referencia = $row['referencia'];
        }
    }

    // 3. Actualizar tabla pagos
    $stmtPago = $pdo->prepare(
        "UPDATE pagos
         SET estado = 'APPROVED',
             stripe_payment_intent_id = COALESCE(:pi, stripe_payment_intent_id),
             updated_at = NOW()
         WHERE stripe_checkout_session_id = :sess
            OR id = :pid
            OR (reserva_id = :rid AND :rid > 0)"
    );
    $stmtPago->execute([
        'pi' => $paymentIntentId,
        'sess' => $sessionId,
        'pid' => $pagoId ?? 0,
        'rid' => $reservaId ?? 0
    ]);

    // 4. Actualizar tabla reservas
    if ($reservaId) {
        $stmtRes = $pdo->prepare(
            "UPDATE reservas
             SET estado = 'APPROVED',
                 metodo_pago = 'Stripe (Pagado con éxito)',
                 fecha_pago = NOW(),
                 stripe_payment_intent_id = COALESCE(:pi, stripe_payment_intent_id),
                 updated_at = NOW()
             WHERE id = :rid"
        );
        $stmtRes->execute([
            'pi' => $paymentIntentId,
            'rid' => $reservaId
        ]);
    } elseif ($sessionId !== '') {
        $stmtRes = $pdo->prepare(
            "UPDATE reservas
             SET estado = 'APPROVED',
                 metodo_pago = 'Stripe (Pagado con éxito)',
                 fecha_pago = NOW(),
                 stripe_payment_intent_id = COALESCE(:pi, stripe_payment_intent_id),
                 updated_at = NOW()
             WHERE stripe_checkout_session_id = :sess"
        );
        $stmtRes->execute([
            'pi' => $paymentIntentId,
            'sess' => $sessionId
        ]);
    }

    $pdo->commit();

    // 5. Intentar enviar correo de confirmación de pago si tenemos datos
    try {
        if ($reservaId) {
            $stmtInfo = $pdo->prepare(
                'SELECT r.referencia, r.cantidad_personas, r.monto_centavos, u.correo, u."nombreCompleto" AS nombre, v.destino
                 FROM reservas r
                 INNER JOIN usuarios u ON u.id = r.usuario_id
                 INNER JOIN viajes v ON v.id = r.viaje_id
                 WHERE r.id = ? LIMIT 1'
            );
            $stmtInfo->execute([$reservaId]);
            $info = $stmtInfo->fetch();
            if ($info && is_callable('enviarCorreoConfirmacionPago')) {
                enviarCorreoConfirmacionPago($info);
            }
        }
    } catch (Throwable $mailEx) {
        error_log('Error al enviar correo tras confirmar pago: ' . $mailEx->getMessage());
    }

    responder(200, [
        'ok' => true,
        'mensaje' => 'Pago y reserva confirmados correctamente con estado Aprobado',
        'reserva_id' => $reservaId,
        'referencia' => $referencia,
        'estado' => 'APPROVED'
    ]);

} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error confirmar pago: ' . $error->getMessage());
    responder(500, [
        'ok' => false, 
        'error' => 'No fue posible confirmar el pago: ' . $error->getMessage()
    ]);
}