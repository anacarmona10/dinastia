<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

function responder(int $codigo, array $respuesta): never
{
    http_response_code($codigo);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, ['ok' => false, 'mensaje' => 'Método no permitido.']);
}

if (empty($_SESSION['logged_in']) || ($_SESSION['tipo_usuario'] ?? '') !== 'usuario') {
    responder(401, ['ok' => false, 'mensaje' => 'Debes iniciar sesión.']);
}

require_once __DIR__ . '/conexion.php';

$datos = json_decode(file_get_contents('php://input'), true);
$actual = (string) ($datos['actual'] ?? '');
$nueva = (string) ($datos['nueva'] ?? '');

if ($actual === '' || $nueva === '') {
    responder(422, ['ok' => false, 'mensaje' => 'Completa ambos campos de contraseña.']);
}

if (strlen($nueva) < 6) {
    responder(422, ['ok' => false, 'mensaje' => 'La nueva contraseña debe tener al menos 6 caracteres.']);
}

try {
    $consulta = $pdo->prepare('SELECT contraseña FROM usuarios WHERE id = :id');
    $consulta->execute(['id' => $_SESSION['user_id']]);
    $usuario = $consulta->fetch();

    if (!$usuario || !password_verify($actual, $usuario['contraseña'])) {
        responder(422, ['ok' => false, 'mensaje' => 'La contraseña actual no es correcta.']);
    }

    $actualizar = $pdo->prepare(
        'UPDATE usuarios SET contraseña = :contrasena WHERE id = :id'
    );
    $actualizar->execute([
        'contrasena' => password_hash($nueva, PASSWORD_DEFAULT),
        'id' => $_SESSION['user_id'],
    ]);

    responder(200, ['ok' => true, 'mensaje' => 'Contraseña actualizada correctamente.']);
} catch (Throwable $error) {
    error_log('Error cambiando contraseña: ' . $error->getMessage());
    responder(500, ['ok' => false, 'mensaje' => 'No fue posible actualizar la contraseña.']);
}
