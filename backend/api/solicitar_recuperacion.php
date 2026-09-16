<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/enviar_correo.php';

function responderRecuperacion(int $codigo, array $datos): never
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderRecuperacion(405, ['ok' => false, 'mensaje' => 'Método no permitido.']);
}

$entrada = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($entrada)) {
    $entrada = $_POST;
}

$correo = trim((string) ($entrada['correo'] ?? ''));
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    responderRecuperacion(422, ['ok' => false, 'mensaje' => 'Ingresa un correo electrónico válido.']);
}

$ultimoEnvio = (int) ($_SESSION['recuperacion_ultimo_envio'] ?? 0);
if ($ultimoEnvio > 0 && time() - $ultimoEnvio < 60) {
    responderRecuperacion(429, [
        'ok' => false,
        'mensaje' => 'Espera un minuto antes de solicitar otro código.'
    ]);
}

try {
    $consulta = $pdo->prepare(
        'SELECT id, correo, "nombreCompleto" FROM usuarios WHERE correo = :correo LIMIT 1'
    );
    $consulta->execute(['correo' => $correo]);
    $usuario = $consulta->fetch();

    // El mensaje no revela si el correo tiene una cuenta, para evitar enumeración de usuarios.
    $mensajeSeguro = 'Si el correo está registrado, recibirás un código de recuperación.';

    if (!$usuario) {
        responderRecuperacion(200, ['ok' => true, 'mensaje' => $mensajeSeguro]);
    }

    $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    $_SESSION['recuperacion_usuario_id'] = (int) $usuario['id'];
    $_SESSION['recuperacion_codigo_hash'] = password_hash($codigo, PASSWORD_DEFAULT);
    $_SESSION['recuperacion_codigo_expira'] = time() + 600;
    $_SESSION['recuperacion_intentos'] = 0;
    $_SESSION['recuperacion_ultimo_envio'] = time();

    if (!enviarCorreoRecuperacion(
        (string) $usuario['correo'],
        (string) $usuario['nombreCompleto'],
        $codigo
    )) {
        unset(
            $_SESSION['recuperacion_usuario_id'],
            $_SESSION['recuperacion_codigo_hash'],
            $_SESSION['recuperacion_codigo_expira'],
            $_SESSION['recuperacion_intentos']
        );

        responderRecuperacion(503, [
            'ok' => false,
            'mensaje' => 'No fue posible enviar el código. Inténtalo nuevamente.'
        ]);
    }

    responderRecuperacion(200, ['ok' => true, 'mensaje' => $mensajeSeguro]);
} catch (Throwable $error) {
    error_log('Error solicitando recuperación de contraseña: ' . $error->getMessage());
    responderRecuperacion(500, [
        'ok' => false,
        'mensaje' => 'No fue posible procesar la solicitud. Inténtalo nuevamente.'
    ]);
}
