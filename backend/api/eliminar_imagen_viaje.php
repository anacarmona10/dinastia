<?php
session_start();
require_once __DIR__ . '/conexion.php';

header('Content-Type: application/json; charset=utf-8');

// 1. Verificación de sesión de administrador
if (empty($_SESSION['logged_in']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Debes iniciar sesión como administrador para eliminar imágenes.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido. Utiliza POST.'
    ]);
    exit;
}

try {
    $imagenId = $_POST['imagen_id'] ?? $_POST['id'] ?? null;

    if (!$imagenId || !is_numeric($imagenId)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Identificador de imagen no proporcionado o inválido.'
        ]);
        exit;
    }

    $imagenId = (int)$imagenId;

    // 2. Consultar la imagen en la base de datos para obtener el nombre del archivo
    $stmt = $pdo->prepare('SELECT id, viaje_id, url FROM imagenes_viajes WHERE id = ?');
    $stmt->execute([$imagenId]);
    $imagen = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$imagen) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'La imagen que intentas eliminar no existe en el sistema.'
        ]);
        exit;
    }

    $nombreArchivo = $imagen['url'];
    $viajeId = $imagen['viaje_id'];

    // 3. Eliminar archivo físico si existe en frontend/imagenes/
    if (!empty($nombreArchivo) && !preg_match('/^https?:\/\//i', $nombreArchivo)) {
        $rutaFisica = __DIR__ . '/../../frontend/imagenes/' . basename($nombreArchivo);
        if (file_exists($rutaFisica) && is_file($rutaFisica)) {
            @unlink($rutaFisica);
        }
    }

    // 4. Eliminar el registro en la base de datos
    $deleteStmt = $pdo->prepare('DELETE FROM imagenes_viajes WHERE id = ?');
    $deleteStmt->execute([$imagenId]);

    echo json_encode([
        'success' => true,
        'mensaje' => 'Imagen eliminada correctamente.',
        'imagen_id' => $imagenId,
        'viaje_id' => $viajeId
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log('Error PDO al eliminar imagen: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error en base de datos al eliminar la imagen: ' . $e->getMessage()
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('Error general al eliminar imagen: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error interno al procesar la eliminación: ' . $e->getMessage()
    ]);
}
