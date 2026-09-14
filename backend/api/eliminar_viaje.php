<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/conexion.php";
require_once __DIR__ . "/validaciones.php";

header('Content-Type: application/json; charset=utf-8');

// 1. Validar permisos de administrador
if (!puedeGestionarViajes($_SESSION)) {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "error" => "Debes iniciar sesión como administrador"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 2. Recibir ID del viaje (vía POST FormData o JSON)
    $id = $_POST['id'] ?? null;
    if ($id === null) {
        $rawInput = file_get_contents('php://input');
        if (!empty($rawInput)) {
            $jsonData = json_decode($rawInput, true);
            $id = $jsonData['id'] ?? null;
        }
    }

    // 3. Validar ID
    $errores = validarIdViaje($id);
    if (!empty($errores)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => $errores[0]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $id = (int)$id;

    // 4. Comprobar existencia del viaje
    $stmt = $pdo->prepare("SELECT id, destino FROM viajes WHERE id = ?");
    $stmt->execute([$id]);
    $viaje = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$viaje) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "error" => "El viaje no existe o ya fue eliminado"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 5. Validar integridad referencial (no borrar si tiene reservas o pagos)
    $stmtPagos = $pdo->prepare("SELECT COUNT(*) FROM pagos WHERE viaje_id = ?");
    $stmtPagos->execute([$id]);
    $pagosCount = (int) $stmtPagos->fetchColumn();

    $stmtReservas = $pdo->prepare("SELECT COUNT(*) FROM reservas WHERE viaje_id = ?");
    $stmtReservas->execute([$id]);
    $reservasCount = (int) $stmtReservas->fetchColumn();

    if ($pagosCount > 0 || $reservasCount > 0) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "error" => "No es posible eliminar este viaje porque cuenta con reservas o pagos registrados."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 6. Transacción para borrar imágenes asociadas y el viaje
    $pdo->beginTransaction();

    // Obtener nombres de imágenes asociadas
    $stmtImg = $pdo->prepare("SELECT url FROM imagenes_viajes WHERE viaje_id = ?");
    $stmtImg->execute([$id]);
    $imagenes = $stmtImg->fetchAll(PDO::FETCH_COLUMN);

    // Borrar registros en imagenes_viajes
    $stmtDelImg = $pdo->prepare("DELETE FROM imagenes_viajes WHERE viaje_id = ?");
    $stmtDelImg->execute([$id]);

    // Borrar registro en viajes
    $stmtDelViaje = $pdo->prepare("DELETE FROM viajes WHERE id = ?");
    $stmtDelViaje->execute([$id]);

    $pdo->commit();

    // 7. Eliminar archivos físicos de las imágenes en frontend/imagenes/
    $carpeta = dirname(__DIR__, 2) . '/frontend/imagenes/';
    foreach ($imagenes as $nombreImg) {
        if (!empty($nombreImg) && !preg_match('#^https?://#i', (string)$nombreImg)) {
            $rutaArchivo = $carpeta . $nombreImg;
            if (file_exists($rutaArchivo)) {
                @unlink($rutaArchivo);
            }
        }
    }

    echo json_encode([
        "success" => true,
        "mensaje" => "Viaje eliminado correctamente"
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Error en la base de datos: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
