<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/validaciones.php';

function responderReporteVentas(int $codigo, array $datos): never
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

// Esta validación protege los datos incluso si alguien intenta abrir la API directamente.
if (!puedeGestionarViajes($_SESSION)) {
    responderReporteVentas(403, [
        'success' => false,
        'error' => 'Debes iniciar sesión como administrador para consultar reportes.'
    ]);
}

require_once __DIR__ . '/conexion.php';

try {
    // Se toma un único pago por reserva. Solo entran ventas confirmadas; pendientes y
    // canceladas no se contabilizan como ingresos.
    $sql = <<<'SQL'
        SELECT
            r.id AS reserva_id,
            COALESCE(p.referencia, r.referencia) AS referencia,
            u."nombreCompleto" AS cliente,
            u.correo AS cliente_correo,
            v.destino,
            COALESCE(p.cantidad_personas, r.cantidad_personas, 1) AS cantidad_personas,
            COALESCE(p.monto_centavos, r.monto_centavos, 0) AS monto_centavos,
            COALESCE(r.metodo_pago, 'Stripe') AS metodo_pago,
            COALESCE(r.fecha_pago, p.updated_at, p.created_at, r.created_at, r.fecha_reserva) AS fecha_venta
        FROM reservas AS r
        INNER JOIN usuarios AS u ON u.id = r.usuario_id
        INNER JOIN viajes AS v ON v.id = r.viaje_id
        LEFT JOIN LATERAL (
            SELECT id, referencia, cantidad_personas, monto_centavos, estado, created_at, updated_at
            FROM pagos
            WHERE reserva_id = r.id
            ORDER BY id DESC
            LIMIT 1
        ) AS p ON TRUE
        WHERE
            UPPER(COALESCE(p.estado, '')) = 'APPROVED'
            OR UPPER(COALESCE(r.estado, '')) IN ('APPROVED', 'PAGADO', 'PAGADA', 'PAGADO CON ÉXITO')
        ORDER BY fecha_venta DESC NULLS LAST, r.id DESC
    SQL;

    $ventas = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $hoy = new DateTimeImmutable('today');
    $inicioMes = $hoy->modify('first day of this month')->format('Y-m-d');
    $inicioUltimos30Dias = $hoy->modify('-29 days')->format('Y-m-d');

    $ingresosCentavos = 0;
    $ingresosMesCentavos = 0;
    $ventasMes = 0;
    $ventasUltimos30Dias = 0;
    $porDestino = [];

    foreach ($ventas as &$venta) {
        $monto = max(0, (int) ($venta['monto_centavos'] ?? 0));
        $venta['monto_centavos'] = $monto;
        $venta['cantidad_personas'] = max(1, (int) ($venta['cantidad_personas'] ?? 1));

        $fecha = substr((string) ($venta['fecha_venta'] ?? ''), 0, 10);
        $venta['fecha_venta'] = $fecha;

        $ingresosCentavos += $monto;

        if ($fecha !== '' && $fecha >= $inicioMes) {
            $ingresosMesCentavos += $monto;
            $ventasMes++;
        }

        if ($fecha !== '' && $fecha >= $inicioUltimos30Dias) {
            $ventasUltimos30Dias++;
        }

        $destino = trim((string) ($venta['destino'] ?? 'Sin destino'));
        if (!isset($porDestino[$destino])) {
            $porDestino[$destino] = [
                'destino' => $destino,
                'ventas' => 0,
                'ingresos_centavos' => 0,
            ];
        }
        $porDestino[$destino]['ventas']++;
        $porDestino[$destino]['ingresos_centavos'] += $monto;
    }
    unset($venta);

    $ventasPorDestino = array_values($porDestino);
    usort(
        $ventasPorDestino,
        static fn (array $a, array $b): int => $b['ingresos_centavos'] <=> $a['ingresos_centavos']
    );

    responderReporteVentas(200, [
        'success' => true,
        'resumen' => [
            'ventas_confirmadas' => count($ventas),
            'ingresos_centavos' => $ingresosCentavos,
            'ventas_mes' => $ventasMes,
            'ingresos_mes_centavos' => $ingresosMesCentavos,
            'ventas_ultimos_30_dias' => $ventasUltimos30Dias,
            'ticket_promedio_centavos' => count($ventas) > 0
                ? (int) round($ingresosCentavos / count($ventas))
                : 0,
        ],
        'ventas_por_destino' => array_slice($ventasPorDestino, 0, 5),
        'ventas' => $ventas,
    ]);
} catch (Throwable $error) {
    error_log('Error generando reportes de ventas: ' . $error->getMessage());
    responderReporteVentas(500, [
        'success' => false,
        'error' => 'No fue posible generar el reporte de ventas.'
    ]);
}
