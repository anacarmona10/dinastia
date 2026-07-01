<?php
session_start();
require "conexion.php";

// Solo un admin con sesión iniciada puede ver el listado
if (empty($_SESSION['logged_in']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "error" => "Debes iniciar sesión como administrador"
    ]);
    exit;
}

try {
    // 1. Traer todos los viajes
    $sql = "SELECT id, destino, descripcion, precio, fecha_salida, fecha_regreso
            FROM viajes
            ORDER BY id DESC";
    $stmt = $pdo->query($sql);
    $viajes = $stmt->fetchAll();

    // 2. Traer todas las imágenes en una sola consulta (evita hacer 1 query por viaje)
    $sqlImg = "SELECT id, viaje_id, url FROM imagenes_viajes ORDER BY id ASC";
    $stmtImg = $pdo->query($sqlImg);
    $imagenesRaw = $stmtImg->fetchAll();

    // 3. Agrupar imágenes por viaje_id
    $imagenesPorViaje = [];
    foreach ($imagenesRaw as $img) {
        $imagenesPorViaje[$img['viaje_id']][] = [
            "id"  => $img['id'],
            "url" => $img['url'] // nombre de archivo, ej: 1751364000_foto.jpg
        ];
    }

    // 4. Adjuntar imágenes a cada viaje
    foreach ($viajes as &$viaje) {
        $viaje['imagenes'] = $imagenesPorViaje[$viaje['id']] ?? [];
    }
    unset($viaje);

    header('Content-Type: application/json');
    echo json_encode([
        "success" => true,
        "viajes"  => $viajes
    ]);

} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}