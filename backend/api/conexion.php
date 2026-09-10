<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

/*
|--------------------------------------------------------------------------
| Cargar .env
|--------------------------------------------------------------------------
| El archivo .env está en la raíz del proyecto:
| dinastia/.env
*/
$raizProyecto = dirname(__DIR__, 2);

if (file_exists($raizProyecto . '/.env')) {
    Dotenv::createImmutable($raizProyecto)->safeLoad();
}

/*
|--------------------------------------------------------------------------
| Leer variables de Docker o .env
|--------------------------------------------------------------------------
*/
function variableEntorno(string $nombre, string $porDefecto = ''): string
{
    $valor = $_ENV[$nombre] ?? $_SERVER[$nombre] ?? getenv($nombre);

    if ($valor === false || $valor === null) {
        return $porDefecto;
    }

    return trim((string) $valor);
}

$host = variableEntorno('DB_HOST');
$port = variableEntorno('DB_PORT', '5432');
$dbname = variableEntorno('DB_NAME');
$user = variableEntorno('DB_USER');
$password = variableEntorno('DB_PASSWORD');
$sslmode = variableEntorno('DB_SSLMODE', 'require');

if ($host === '' || $dbname === '' || $user === '' || $password === '') {
    http_response_code(500);
    exit('Error: faltan variables de conexión a la base de datos.');
}

/*
|--------------------------------------------------------------------------
| Neon requiere endpoint cuando el cliente PostgreSQL no soporta SNI
|--------------------------------------------------------------------------
*/
$endpointId = explode('.', $host)[0];

$dsn = sprintf(
    'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s;options=endpoint=%s',
    $host,
    $port,
    $dbname,
    $sslmode,
    $endpointId
);

try {
    $pdo = new PDO(
        $dsn,
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $error) {
    error_log('Error PostgreSQL/Neon: ' . $error->getMessage());

    http_response_code(500);
    exit('No fue posible conectar con la base de datos.');
}