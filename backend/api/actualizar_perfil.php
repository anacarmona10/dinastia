<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);
    exit;
}

if (empty($_SESSION['logged_in']) || ($_SESSION['tipo_usuario'] ?? '') !== 'usuario') {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Debes iniciar sesión.'
    ]);
    exit;
}

require_once 'conexion.php';

$datos = json_decode(file_get_contents('php://input'), true);

$nombreCompleto = trim($datos['nombreCompleto'] ?? '');
$tipoDocumento = trim($datos['tipoDocumento'] ?? '');
$numeroDocumento = trim($datos['numeroDocumento'] ?? '');
$correo = trim($datos['correo'] ?? '');

if ($nombreCompleto === '' || $tipoDocumento === '' || $numeroDocumento === '' || $correo === '') {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Todos los campos son obligatorios.'
    ]);
    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'El correo no tiene un formato válido.'
    ]);
    exit;
}

$tiposPermitidos = ['cc', 'ce', 'ppt', 'pep', 'passport'];

if (!in_array($tipoDocumento, $tiposPermitidos, true)) {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'El tipo de documento no es válido.'
    ]);
    exit;
}

try {
    $usuarioId = $_SESSION['user_id'];

    $verificarCorreo = $pdo->prepare('
        SELECT id
        FROM usuarios
        WHERE correo = :correo AND id <> :id
    ');

    $verificarCorreo->execute([
        'correo' => $correo,
        'id' => $usuarioId
    ]);

    if ($verificarCorreo->fetch()) {
        http_response_code(409);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Ese correo ya está registrado por otro usuario.'
        ]);
        exit;
    }

    $actualizar = $pdo->prepare('
        UPDATE usuarios
        SET
            "nombreCompleto" = :nombre,
            "tipoDocumento" = :tipo_documento,
            "numeroDocumento" = :numero_documento,
            correo = :correo
        WHERE id = :id
    ');

    $actualizar->execute([
        'nombre' => $nombreCompleto,
        'tipo_documento' => $tipoDocumento,
        'numero_documento' => $numeroDocumento,
        'correo' => $correo,
        'id' => $usuarioId
    ]);

    $_SESSION['user_nombre'] = $nombreCompleto;
    $_SESSION['user_email'] = $correo;
    $_SESSION['user_tipo_doc'] = $tipoDocumento;
    $_SESSION['user_num_doc'] = $numeroDocumento;

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Tus datos fueron actualizados correctamente.'
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'No fue posible actualizar el perfil.'
    ]);
}