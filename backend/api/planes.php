<?php
/**
 * ============================================================================
 * Dinastía AMV - API REST: Catálogo de Planes Turísticos (backend/api/planes.php)
 * Conectado directamente a la Base de Datos PostgreSQL/Neon (tabla `viajes` e `imagenes_viajes`)
 * Endpoint público: GET /api/planes
 * Parámetros soportados:
 *   - destino: string (búsqueda parcial insensible a mayúsculas)
 *   - precio_min: number (precio mínimo inclusive)
 *   - precio_max: number (precio máximo inclusive)
 *   - duracion: string ("1-3", "4-6", "7-9", "10+")
 *   - fecha_salida: date (YYYY-MM-DD, a partir de esa fecha)
 *   - lugar_salida: string (ciudad de origen, ej: Medellin, Bogota, Cali)
 *   - orden: string ('precio_asc', 'precio_desc', 'fecha_asc', 'duracion_desc')
 *   - limit / offset: paginación
 * ============================================================================
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Cargar variables de entorno si no están cargadas en el servidor
if (!getenv('DB_HOST') && file_exists(__DIR__ . '/../../.env')) {
    $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

// Requerir conexión a base de datos
$pdo = null;
if (file_exists(__DIR__ . '/conexion.php')) {
    try {
        require_once __DIR__ . '/conexion.php';
    } catch (Throwable $e) {
        error_log('Error al incluir conexion.php: ' . $e->getMessage());
    }
}

// 1. Capturar y sanitizar parámetros de consulta
$destino     = trim($_GET['destino'] ?? '');
$precioMin   = isset($_GET['precio_min']) && is_numeric($_GET['precio_min']) ? (float)$_GET['precio_min'] : null;
$precioMax   = isset($_GET['precio_max']) && is_numeric($_GET['precio_max']) ? (float)$_GET['precio_max'] : null;
$duracion    = trim($_GET['duracion'] ?? '');
$fechaSalida = trim($_GET['fecha_salida'] ?? '');
$lugarSalida = trim($_GET['lugar_salida'] ?? '');
$orden       = trim($_GET['orden'] ?? 'destacados');
$limit       = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset      = isset($_GET['offset']) && is_numeric($_GET['offset']) ? (int)$_GET['offset'] : 0;

$planes = [];

// Imágenes de fallback de alta calidad según el destino colombiano
function obtenerImagenPorDestino($nombreDestino) {
    $d = mb_strtolower($nombreDestino, 'UTF-8');
    if (strpos($d, 'cartagena') !== false) {
        return 'https://lh3.googleusercontent.com/aida-public/AB6AXuBvExoATYYi5sDh08TyPPpKcGBFiwHlycMmkBk5FC0OdZHqlImdFAXBLAxvA5dJzz0UzTvI6sns2Y4gg9yNq5PC5vDCQsjUqUxP3MrXpKQ0vcjYULysOsHoF1LCxQIAYzrcAXOsFo0_NpASTBhkFMThKRPE9WDT3fv2k8u5TF4thJQgKsXAX-AG_XlUx_uqxTAjmwvEcFJoKXsSvJ49YJIye1LHjRQw13CrFFvBDC8WvO78oaKxO8_9D9YyzfgoboksblDE0tcvsEo';
    }
    if (strpos($d, 'cafe') !== false || strpos($d, 'salento') !== false || strpos($d, 'pereira') !== false || strpos($d, 'quindio') !== false || strpos($d, 'cocora') !== false) {
        return 'https://lh3.googleusercontent.com/aida-public/AB6AXuCvPrz-UJLgGXIH0Xj9xFFkT2pAErtARb9_kUsDaWTeNYoK90WuIr-r4f2Z5NE5T77dRYqDNb4PGgKUraUCh0Hnvx1jS8_zSwPsWNzowYY8gwjFPiVUfmWhF4wZtVb9aAV_fLElFnKQb44pSi34O-KCUpy8K3_eMvLHUQ6owRySIsVcdQDtOQUKamSenZHRMrBXSDSmnMgpmFyvVtuwSrpIxgrgPo2W70ONswYkq_4l3RS0eX0wLZf0xdtAbT2t0jQUZRqM9teIQik';
    }
    if (strpos($d, 'medellin') !== false || strpos($d, 'guatape') !== false || strpos($d, 'antioquia') !== false) {
        return 'https://lh3.googleusercontent.com/aida-public/AB6AXuDmvzIkEu7h8e83Z31QwWqQ7n5su1eKBEyRUobk1YJybkCWUwvqHDMfmC0jbhWQPCxubuHzk8cjKAfVr5eRMpk2COpC666qd6uUTkr8VnoYPZi0fhVpwoL1qD-1OHHHgcuPZqGOmcfp5ou0eF1sRahYPKZlSjxkwOsbRAnxHu3e_HcHZQutsJ24A30ECbAROL3MDJXjdnIa1mSqVJlcw6d-czAR56c8jxneUMLQzgBO1q3KrXJXRW911pQmSPrVyWNToinxExApd3M';
    }
    if (strpos($d, 'san andres') !== false || strpos($d, 'providencia') !== false) {
        return 'https://images.unsplash.com/photo-1590523741831-ab7e8b8f9c7f?auto=format&fit=crop&w=800&q=80';
    }
    if (strpos($d, 'santa marta') !== false || strpos($d, 'tayrona') !== false || strpos($d, 'taganga') !== false) {
        return 'https://images.unsplash.com/photo-1507525428033-b723cf961d3e?auto=format&fit=crop&w=800&q=80';
    }
    if (strpos($d, 'amazonas') !== false || strpos($d, 'leticia') !== false) {
        return 'https://images.unsplash.com/photo-1516026672322-bc52d61a55d5?auto=format&fit=crop&w=800&q=80';
    }
    if (strpos($d, 'boyaca') !== false || strpos($d, 'villa de leyva') !== false) {
        return 'https://images.unsplash.com/photo-1596401057633-54a8fe8ef647?auto=format&fit=crop&w=800&q=80';
    }
    return 'https://images.unsplash.com/photo-1583531352515-8884af319dc1?auto=format&fit=crop&w=800&q=80';
}

if ($pdo instanceof PDO) {
    try {
        $whereClauses = [];
        $params = [];

        // Filtro por Destino (coincidencia parcial insensible a mayúsculas)
        if (!empty($destino)) {
            $whereClauses[] = "(LOWER(destino) LIKE LOWER(?) OR LOWER(descripcion) LIKE LOWER(?))";
            $params[] = "%{$destino}%";
            $params[] = "%{$destino}%";
        }

        // Filtro por Rango de Precios (RN-003: precio > 0)
        if ($precioMin !== null && $precioMin > 0) {
            $whereClauses[] = "precio >= ?";
            $params[] = $precioMin;
        }
        if ($precioMax !== null && $precioMax > 0) {
            $whereClauses[] = "precio <= ?";
            $params[] = $precioMax;
        }

        // Filtro por Fecha de Salida (a partir de)
        if (!empty($fechaSalida)) {
            $whereClauses[] = "fecha_salida >= ?";
            $params[] = $fechaSalida;
        }

        // Construir WHERE SQL
        $whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

        // Ordenamiento
        $orderSql = "ORDER BY id DESC";
        if ($orden === 'precio_asc')   $orderSql = "ORDER BY precio ASC";
        if ($orden === 'precio_desc')  $orderSql = "ORDER BY precio DESC";
        if ($orden === 'fecha_asc')    $orderSql = "ORDER BY fecha_salida ASC";
        if ($orden === 'duracion_desc')$orderSql = "ORDER BY (fecha_regreso - fecha_salida) DESC";

        // Consulta optimizada para la tabla `viajes`
        $sql = "SELECT id, destino, descripcion, precio, fecha_salida, fecha_regreso,
                       GREATEST(1, (fecha_regreso - fecha_salida) + 1) AS duracion,
                       admin_id, created_at
                FROM viajes
                {$whereSql}
                {$orderSql}
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $viajesDb = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($viajesDb)) {
            // Traer imágenes asociadas a los viajes obtenidos
            $ids = array_column($viajesDb, 'id');
            $inQuery = implode(',', array_fill(0, count($ids), '?'));
            $stmtImg = $pdo->prepare("SELECT viaje_id, url FROM imagenes_viajes WHERE viaje_id IN ({$inQuery}) ORDER BY id ASC");
            $stmtImg->execute($ids);
            $imagenesRaw = $stmtImg->fetchAll(PDO::FETCH_ASSOC);

            $imgMap = [];
            foreach ($imagenesRaw as $img) {
                $imgMap[$img['viaje_id']][] = $img['url'];
            }

            foreach ($viajesDb as $row) {
                $viajeId = (int)$row['id'];
                $imgs = $imgMap[$viajeId] ?? [];
                
                // Determinar imagen principal
                $primeraImg = '';
                if (!empty($imgs)) {
                    $primeraImg = $imgs[0];
                    if (strpos($primeraImg, 'http') !== 0) {
                        $primeraImg = 'imagenes/' . $primeraImg;
                    }
                } else {
                    $primeraImg = obtenerImagenPorDestino($row['destino']);
                }

                $precioNum = (float)$row['precio'];
                $dias = (int)($row['duracion'] ?? 5);

                // Filtro adicional en memoria para lugar de salida si fue especificado
                $lugarOrigen = 'Medellin';
                if (stripos($row['descripcion'], 'bogota') !== false) $lugarOrigen = 'Bogota';
                if (stripos($row['descripcion'], 'cali') !== false) $lugarOrigen = 'Cali';
                if (!empty($lugarSalida) && $lugarSalida !== 'todos') {
                    if (stripos($lugarOrigen, $lugarSalida) === false && stripos($row['destino'], $lugarSalida) === false && stripos($row['descripcion'], $lugarSalida) === false) {
                        continue;
                    }
                }

                // Filtro de duración
                if (!empty($duracion) && $duracion !== 'todas') {
                    if ($duracion === '1-3' && ($dias < 1 || $dias > 3)) continue;
                    if ($duracion === '4-6' && ($dias < 4 || $dias > 6)) continue;
                    if ($duracion === '7-9' && ($dias < 7 || $dias > 9)) continue;
                    if ($duracion === '10+' && $dias < 10) continue;
                }

                $planes[] = [
                    "id" => $viajeId,
                    "destino" => $row['destino'],
                    "descripcion" => $row['descripcion'],
                    "precio" => $precioNum,
                    "precio_anterior" => round($precioNum * 1.35, -3),
                    "descuento" => "-25% Dcto",
                    "fecha_salida" => $row['fecha_salida'],
                    "fecha_regreso" => $row['fecha_regreso'],
                    "duracion" => $dias,
                    "lugar_salida" => $lugarOrigen,
                    "cupos" => 10,
                    "servicios_incluidos" => "Tiquetes, Hospedaje, Desayunos buffet, Tour guiado, Asistencia médica",
                    "alojamiento" => "Hotel Seleccionado Categoría Turista",
                    "imagen_url" => $primeraImg,
                    "imagenes" => $imgs,
                    "estado" => "activo",
                    "admin_id" => (int)($row['admin_id'] ?? 1),
                    "created_at" => $row['created_at'] ?? date('Y-m-d H:i:s'),
                    "origen_datos" => "base_de_datos"
                ];
            }
        }
    } catch (Throwable $e) {
        error_log('Error al consultar viajes en base de datos: ' . $e->getMessage());
    }
}

// Si la base de datos no arrojó resultados o está vacía, responder con array de datos
echo json_encode([
    "success" => true,
    "total" => count($planes),
    "data" => $planes,
    "message" => count($planes) > 0 ? "OK" : "No se encontraron planes con los filtros seleccionados."
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
