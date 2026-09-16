<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

function responderRestablecimiento(int $codigo, array $datos): never
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function limpiarRecuperacion(): void
{
    unset(
        $_SESSION['recuperacion_usuario_id'],
        $_SESSION['recuperacion_codigo_hash'],
        $_SESSION['recuperacion_codigo_expira'],
        $_SESSION['recuperacion_intentos'],
        $_SESSION['recuperacion_ultimo_envio']
    );
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderRestablecimiento(405, ['ok' => false, 'mensaje' => 'Método no permitido.']);
}

$entrada = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($entrada)) {
    $entrada = $_POST;
}

$codigo = trim((string) ($entrada['codigo'] ?? ''));
$contrasena = (string) ($entrada['contrasena'] ?? '');
$confirmacion = (string) ($entrada['confirmacion'] ?? '');
$usuarioId = (int) ($_SESSION['recuperacion_usuario_id'] ?? 0);
$hashCodigo = (string) ($_SESSION['recuperacion_codigo_hash'] ?? '');
$expira = (int) ($_SESSION['recuperacion_codigo_expira'] ?? 0);
$intentos = (int) ($_SESSION['recuperacion_intentos'] ?? 0);

if ($usuarioId <= 0 || $hashCodigo === '' || $expira <= 0) {
    responderRestablecimiento(403, [
        'ok' => false,
        'mensaje' => 'Solicita un código de recuperación antes de cambiar la contraseña.'
    ]);
}

if (time() > $expira) {
    limpiarRecuperacion();
    responderRestablecimiento(403, [
        'ok' => false,
        'mensaje' => 'El código venció. Solicita uno nuevo.'
    ]);
}

if ($intentos >= 5) {
    limpiarRecuperacion();
    responderRestablecimiento(429, [
        'ok' => false,
        'mensaje' => 'Superaste los intentos permitidos. Solicita un código nuevo.'
    ]);
}

if (!preg_match('/^\d{6}$/', $codigo) || !password_verify($codigo, $hashCodigo)) {
    $_SESSION['recuperacion_intentos'] = $intentos + 1;
    responderRestablecimiento(422, [
        'ok' => false,
        'mensaje' => 'El código no es válido. Verifica e inténtalo nuevamente.'
    ]);
}

if (strlen($contrasena) < 8) {
    responderRestablecimiento(422, [
        'ok' => false,
        'mensaje' => 'La contraseña debe tener al menos 8 caracteres.'
    ]);
}

if ($contrasena !== $confirmacion) {
    responderRestablecimiento(422, [
        'ok' => false,
        'mensaje' => 'Las contraseñas no coinciden.'
    ]);
}

try {
    $actualizar = $pdo->prepare(
        'UPDATE usuarios SET contraseña = :contrasena WHERE id = :id'
    );
    $actualizar->execute([
        'contrasena' => password_hash($contrasena, PASSWORD_DEFAULT),
        'id' => $usuarioId,
    ]);

    limpiarRecuperacion();
    responderRestablecimiento(200, [
        'ok' => true,
        'mensaje' => 'Tu contraseña fue actualizada. Ya puedes iniciar sesión.'
    ]);
} catch (Throwable $error) {
    error_log('Error restableciendo contraseña: ' . $error->getMessage());
    responderRestablecimiento(500, [
        'ok' => false,
        'mensaje' => 'No fue posible actualizar la contraseña. Inténtalo nuevamente.'
    ]);
}
