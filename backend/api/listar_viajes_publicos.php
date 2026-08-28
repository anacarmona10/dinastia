<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

try {
    $consultaViajes = $pdo->query(
        'SELECT id, destino, descripcion, precio, fecha_salida, fecha_regreso
         FROM viajes
         ORDER BY id DESC'
    );

    $viajes = $consultaViajes->fetchAll();

    $consultaImagenes = $pdo->query(
        'SELECT id, viaje_id, url
         FROM imagenes_viajes
         ORDER BY id ASC'
    );

    $imagenesPorViaje = [];

    foreach ($consultaImagenes->fetchAll() as $imagen) {
        $imagenesPorViaje[$imagen['viaje_id']][] = [
            'id' => $imagen['id'],
            'url' => $imagen['url'],
        ];
    }

    foreach ($viajes as &$viaje) {
        $viaje['imagenes'] = $imagenesPorViaje[$viaje['id']] ?? [];
    }
    unset($viaje);

    echo json_encode([
        'success' => true,
        'viajes' => $viajes,
    ]);

} catch (Throwable $error) {
    error_log('Error listando viajes públicos: ' . $error->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'No fue posible cargar los viajes.',
    ]);
}