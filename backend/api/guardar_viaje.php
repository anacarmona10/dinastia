<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require 'conexion.php';

if (empty($_SESSION['logged_in']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Debes iniciar sesion como administrador',
    ]);
    exit;
}

try {
    $destino = trim($_POST['destino'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = $_POST['precio'] ?? '';
    $fechaSalida = $_POST['fecha_salida'] ?? '';
    $fechaRegreso = $_POST['fecha_regreso'] ?? '';
    $adminId = $_SESSION['admin_id'] ?? null;

    if (!$destino || !$descripcion || $precio === '' || !$fechaSalida || !$fechaRegreso || !$adminId) {
        throw new InvalidArgumentException('Completa todos los datos del viaje');
    }

    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        'INSERT INTO viajes (destino, descripcion, precio, fecha_salida, fecha_regreso, admin_id)
         VALUES (?, ?, ?, ?, ?, ?)
         RETURNING id'
    );
    $stmt->execute([$destino, $descripcion, $precio, $fechaSalida, $fechaRegreso, $adminId]);
    $viajeId = $stmt->fetchColumn();

    if (!empty($_FILES['imagenes']['name'][0])) {
        $carpetaImagenes = __DIR__ . '/../../frontend/imagenes/';

        if (!is_dir($carpetaImagenes) && !mkdir($carpetaImagenes, 0755, true) && !is_dir($carpetaImagenes)) {
            throw new RuntimeException('No se pudo preparar la carpeta de imagenes');
        }

        foreach ($_FILES['imagenes']['tmp_name'] as $indice => $archivoTemporal) {
            $nombre = bin2hex(random_bytes(8)) . '_' . basename($_FILES['imagenes']['name'][$indice]);
            $rutaImagen = $carpetaImagenes . $nombre;

            if (!move_uploaded_file($archivoTemporal, $rutaImagen)) {
                throw new RuntimeException('No se pudo guardar una de las imagenes');
            }

            $stmtImagen = $pdo->prepare('INSERT INTO imagenes_viajes (viaje_id, url) VALUES (?, ?)');
            $stmtImagen->execute([$viajeId, $nombre]);
        }
    }

    $pdo->commit();
    echo json_encode([
        'success' => true,
        'mensaje' => 'Viaje guardado correctamente',
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());
    http_response_code($e instanceof InvalidArgumentException ? 422 : 500);
    echo json_encode([
        'success' => false,
        'error' => $e instanceof InvalidArgumentException ? $e->getMessage() : 'No fue posible guardar el viaje',
    ]);
}
