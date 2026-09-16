<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

// Validar que el usuario tenga rol de administrador
if (empty($_SESSION['logged_in']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Debes iniciar sesión como administrador',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $destino = trim($_POST['destino'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = $_POST['precio'] ?? '';
    $fechaSalida = trim($_POST['fecha_salida'] ?? '');
    $fechaRegreso = trim($_POST['fecha_regreso'] ?? '');

    // Validar campos obligatorios
    if ($destino === '' || $precio === '' || $fechaSalida === '' || $fechaRegreso === '') {
        throw new InvalidArgumentException('Completa todos los campos obligatorios: destino, precio, fecha de salida y regreso.');
    }

    if (!is_numeric($precio) || (float)$precio <= 0) {
        throw new InvalidArgumentException('El precio debe ser un número válido mayor a cero.');
    }

    if ($fechaRegreso < $fechaSalida) {
        throw new InvalidArgumentException('La fecha de regreso no puede ser anterior a la fecha de salida.');
    }

    // Resolver admin_id de forma robusta para garantizar la integridad referencial en PostgreSQL
    $adminId = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;

    if ($adminId) {
        $stmtCheck = $pdo->prepare('SELECT id FROM admins WHERE id = ?');
        $stmtCheck->execute([$adminId]);
        if (!$stmtCheck->fetchColumn()) {
            $adminId = null;
        }
    }

    if (!$adminId && !empty($_SESSION['admin_email'])) {
        $stmtAdmin = $pdo->prepare('SELECT id FROM admins WHERE correo = ? LIMIT 1');
        $stmtAdmin->execute([$_SESSION['admin_email']]);
        $adminId = $stmtAdmin->fetchColumn();
    }

    if (!$adminId) {
        $adminId = $pdo->query('SELECT id FROM admins ORDER BY id ASC LIMIT 1')->fetchColumn();
    }

    if (!$adminId) {
        throw new RuntimeException('No se encontró ningún administrador válido registrado en la base de datos.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO viajes (destino, descripcion, precio, fecha_salida, fecha_regreso, admin_id)
         VALUES (?, ?, ?, ?, ?, ?)
         RETURNING id'
    );
    $stmt->execute([$destino, $descripcion, (float)$precio, $fechaSalida, $fechaRegreso, $adminId]);
    $viajeId = $stmt->fetchColumn();

    if (!$viajeId) {
        throw new RuntimeException('No se pudo obtener el ID del viaje registrado.');
    }

    // Procesar imágenes si fueron enviadas
    $imagenesGuardadas = 0;
    if (!empty($_FILES['imagenes']['name']) && is_array($_FILES['imagenes']['name'])) {
        $carpetaImagenes = dirname(__DIR__, 2) . '/frontend/imagenes/';

        if (!is_dir($carpetaImagenes)) {
            @mkdir($carpetaImagenes, 0777, true);
        }
        @chmod($carpetaImagenes, 0777);

        $stmtImagen = $pdo->prepare('INSERT INTO imagenes_viajes (viaje_id, url) VALUES (?, ?)');

        foreach ($_FILES['imagenes']['tmp_name'] as $indice => $archivoTemporal) {
            $nombreOriginal = $_FILES['imagenes']['name'][$indice] ?? '';
            $errorSubida = $_FILES['imagenes']['error'][$indice] ?? UPLOAD_ERR_NO_FILE;

            if ($errorSubida === UPLOAD_ERR_NO_FILE || empty($nombreOriginal)) {
                continue;
            }

            if ($errorSubida === UPLOAD_ERR_INI_SIZE || $errorSubida === UPLOAD_ERR_FORM_SIZE) {
                error_log("Aviso: la imagen '{$nombreOriginal}' supera el tamaño máximo permitido por PHP.");
                continue;
            }

            if ($errorSubida !== UPLOAD_ERR_OK || empty($archivoTemporal) || !is_uploaded_file($archivoTemporal)) {
                continue;
            }

            $nombreLimpio = bin2hex(random_bytes(8)) . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($nombreOriginal));
            $rutaDestino = $carpetaImagenes . $nombreLimpio;

            if (@move_uploaded_file($archivoTemporal, $rutaDestino)) {
                $stmtImagen->execute([$viajeId, $nombreLimpio]);
                $imagenesGuardadas++;
            } else {
                error_log("Aviso: no fue posible mover la imagen '{$nombreOriginal}' a '{$rutaDestino}'.");
            }
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'mensaje' => 'Viaje guardado correctamente',
        'viaje_id' => (int)$viajeId,
        'imagenes_guardadas' => $imagenesGuardadas,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Error al guardar viaje: ' . $e->getMessage());
    http_response_code($e instanceof InvalidArgumentException ? 422 : 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
