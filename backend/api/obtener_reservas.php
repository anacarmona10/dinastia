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

if (empty($_SESSION['logged_in']) || ($_SESSION['tipo_usuario'] ?? '') !== 'usuario') {
    responder(401, ['ok' => false, 'mensaje' => 'Debes iniciar sesión.']);
}

require_once __DIR__ . '/conexion.php';

try {
    $consulta = $pdo->prepare(
        'SELECT
            p.id,
            p.referencia,
            p.cantidad_personas,
            p.monto_centavos,
            p.estado,
            p.updated_at AS fecha_pago,
            v.destino,
            v.fecha_salida,
            v.fecha_regreso,
            (
                SELECT url
                FROM imagenes_viajes
                WHERE viaje_id = v.id
                ORDER BY id ASC
                LIMIT 1
            ) AS imagen
         FROM pagos AS p
         INNER JOIN viajes AS v ON v.id = p.viaje_id
         WHERE p.usuario_id = :usuario_id
         ORDER BY v.fecha_salida ASC, p.id DESC'
    );

    $consulta->execute(['usuario_id' => $_SESSION['user_id']]);
    $reservas = [];

    foreach ($consulta->fetchAll() as $pago) {
        $estado = match ($pago['estado']) {
            'APPROVED' => 'pagada',
            'FAILED', 'EXPIRED' => 'cancelada',
            default => 'pendiente',
        };

        $tipoTab = $pago['fecha_regreso'] < date('Y-m-d')
            ? 'pasados'
            : 'proximos';

        $imagen = $pago['imagen'] ?: 'https://via.placeholder.com/600x400?text=Sin+imagen';
        if ($pago['imagen'] && !preg_match('#^https?://#i', $pago['imagen'])) {
            $imagen = 'imagenes/' . rawurlencode($pago['imagen']);
        }

        $reservas[] = [
            'id' => (int) $pago['id'],
            'codigo' => $pago['referencia'],
            'destino' => $pago['destino'],
            'departamento' => 'Colombia',
            'imagen' => $imagen,
            'fechaSalida' => $pago['fecha_salida'],
            'fechaRegreso' => $pago['fecha_regreso'],
            'fechasFormato' => $pago['fecha_salida'] . ' al ' . $pago['fecha_regreso'],
            'personas' => (int) $pago['cantidad_personas'],
            'personasTexto' => $pago['cantidad_personas'] . ' viajero(s)',
            'estado' => $estado,
            'tipoTab' => $tipoTab,
            'alojamiento' => 'Información de alojamiento por confirmar',
            'incluye' => ['Plan turístico según la descripción del viaje'],
            'desglose' => [
                'tarifaBase' => (int) $pago['monto_centavos'],
                'impuestosIva' => 0,
                'totalCOP' => (int) $pago['monto_centavos'] / 100,
            ],
            'metodoPago' => $estado === 'pagada' ? 'Pago confirmado por Stripe' : 'Pendiente de confirmación',
            'fechaPago' => $pago['fecha_pago'] ?? 'Pendiente',
        ];
    }

    $totalProximos = count(array_filter($reservas, fn (array $reserva) => $reserva['tipoTab'] === 'proximos'));
    $totalPasados = count($reservas) - $totalProximos;

    responder(200, [
        'ok' => true,
        'reservas' => $reservas,
        'totalProximos' => $totalProximos,
        'totalPasados' => $totalPasados,
    ]);
} catch (Throwable $error) {
    error_log('Error obteniendo reservas: ' . $error->getMessage());
    responder(500, ['ok' => false, 'mensaje' => 'No fue posible consultar las reservas.']);
}
