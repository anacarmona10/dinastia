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
            r.id,
            r.referencia,
            r.cantidad_personas,
            r.monto_centavos,
            r.estado AS reserva_estado,
            r.metodo_pago,
            r.fecha_reserva,
            r.fecha_pago,
            v.id AS viaje_id,
            v.destino,
            v.fecha_salida,
            v.fecha_regreso,
            p.id AS pago_id,
            p.estado AS pago_estado,
            (
                SELECT url
                FROM imagenes_viajes
                WHERE viaje_id = v.id
                ORDER BY id ASC
                LIMIT 1
            ) AS imagen
         FROM reservas AS r
         INNER JOIN viajes AS v ON v.id = r.viaje_id
         LEFT JOIN pagos AS p ON p.reserva_id = r.id
         WHERE r.usuario_id = :usuario_id
         ORDER BY v.fecha_salida ASC, r.id DESC'
    );

    $consulta->execute(['usuario_id' => $_SESSION['user_id']]);
    $reservas = [];

    foreach ($consulta->fetchAll() as $row) {
        $estadoRaw = strtoupper(trim((string)($row['reserva_estado'] ?? '')));
        $pagoEstadoRaw = strtoupper(trim((string)($row['pago_estado'] ?? '')));

        if (in_array($estadoRaw, ['APPROVED', 'PAGADO', 'PAGADA', 'PAGADO CON ÉXITO']) || $pagoEstadoRaw === 'APPROVED') {
            $estado = 'pagada';
        } elseif (in_array($estadoRaw, ['CANCELLED', 'CANCELADO', 'CANCELADA', 'FAILED', 'EXPIRED']) || in_array($pagoEstadoRaw, ['CANCELLED', 'FAILED', 'EXPIRED'])) {
            $estado = 'cancelada';
        } else {
            $estado = 'pendiente';
        }

        $tipoTab = $row['fecha_regreso'] < date('Y-m-d')
            ? 'pasados'
            : 'proximos';

        $imagen = $row['imagen'] ?: 'https://via.placeholder.com/600x400?text=Sin+imagen';
        if ($row['imagen'] && !preg_match('#^https?://#i', $row['imagen'])) {
            $imagen = 'imagenes/' . rawurlencode($row['imagen']);
        }

        $metodo = $row['metodo_pago'] ?: ($estado === 'pagada' ? 'Pago confirmado por Stripe' : 'Pagar después / En destino');
        $fechaPagoTexto = $row['fecha_pago'] ? date('Y-m-d H:i', strtotime($row['fecha_pago'])) : ($estado === 'pagada' ? 'Confirmado' : 'Pendiente');

        $reservas[] = [
            'id' => (int) $row['id'],
            'reserva_id' => (int) $row['id'],
            'viaje_id' => (int) $row['viaje_id'],
            'pago_id' => $row['pago_id'] ? (int) $row['pago_id'] : null,
            'codigo' => $row['referencia'] ?: ('AMV-RES-' . $row['id']),
            'destino' => $row['destino'],
            'departamento' => 'Colombia',
            'imagen' => $imagen,
            'fechaSalida' => $row['fecha_salida'],
            'fechaRegreso' => $row['fecha_regreso'],
            'fechasFormato' => $row['fecha_salida'] . ' al ' . $row['fecha_regreso'],
            'personas' => (int) $row['cantidad_personas'],
            'personasTexto' => $row['cantidad_personas'] . ' viajero(s)',
            'estado' => $estado,
            'tipoTab' => $tipoTab,
            'alojamiento' => 'Información de alojamiento por confirmar',
            'incluye' => ['Plan turístico según la descripción del viaje'],
            'desglose' => [
                'tarifaBase' => (int) $row['monto_centavos'],
                'impuestosIva' => 0,
                'totalCOP' => (int) $row['monto_centavos'] / 100,
            ],
            'metodoPago' => $metodo,
            'fechaPago' => $fechaPagoTexto,
            'fechaReserva' => $row['fecha_reserva'],
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
