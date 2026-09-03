<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['logged_in']) || ($_SESSION['tipo_usuario'] ?? '') !== 'usuario') {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Debes iniciar sesión.'
    ]);
    exit;
}

require_once 'conexion.php';

try {
    $stmt = $pdo->prepare('
        SELECT
            id,
            "nombreCompleto" AS nombre_completo,
            "tipoDocumento" AS tipo_documento,
            "numeroDocumento" AS numero_documento,
            correo
        FROM usuarios
        WHERE id = :id
    ');

    $stmt->execute(['id' => $_SESSION['user_id']]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        http_response_code(404);
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Usuario no encontrado.'
        ]);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'usuario' => $usuario
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'mensaje' => 'No fue posible consultar el perfil.'
    ]);
}