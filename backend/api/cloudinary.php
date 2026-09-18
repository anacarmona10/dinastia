<?php
declare(strict_types=1);

use Cloudinary\Cloudinary;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Crea el cliente de Cloudinary a partir de las variables de entorno.
 * Las credenciales solo viven en .env local o en las variables de Render.
 */
function clienteCloudinary(): Cloudinary
{
    static $cliente = null;

    if ($cliente instanceof Cloudinary) {
        return $cliente;
    }

    $cloudName = trim((string) ($_ENV['CLOUDINARY_CLOUD_NAME'] ?? $_SERVER['CLOUDINARY_CLOUD_NAME'] ?? getenv('CLOUDINARY_CLOUD_NAME')));
    $apiKey = trim((string) ($_ENV['CLOUDINARY_API_KEY'] ?? $_SERVER['CLOUDINARY_API_KEY'] ?? getenv('CLOUDINARY_API_KEY')));
    $apiSecret = trim((string) ($_ENV['CLOUDINARY_API_SECRET'] ?? $_SERVER['CLOUDINARY_API_SECRET'] ?? getenv('CLOUDINARY_API_SECRET')));

    if ($cloudName === '' || $apiKey === '' || $apiSecret === '') {
        throw new RuntimeException('Faltan las variables de Cloudinary en el servidor.');
    }

    $cliente = new Cloudinary([
        'cloud' => [
            'cloud_name' => $cloudName,
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
        ],
        'url' => [
            'secure' => true,
        ],
    ]);

    return $cliente;
}

/**
 * Sube una foto de un plan turístico y devuelve su URL HTTPS de Cloudinary.
 */
function subirImagenPlanACloudinary(string $archivoTemporal, string $nombreOriginal): string
{
    if (!is_file($archivoTemporal)) {
        throw new InvalidArgumentException('No fue posible leer una de las imágenes seleccionadas.');
    }

    $tamano = filesize($archivoTemporal);
    if ($tamano === false || $tamano > 8 * 1024 * 1024) {
        throw new InvalidArgumentException('Cada imagen puede pesar máximo 8 MB.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivoTemporal);
    $formatosPermitidos = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/avif',
    ];

    if (!in_array($mime, $formatosPermitidos, true)) {
        throw new InvalidArgumentException("La imagen '{$nombreOriginal}' debe ser JPG, PNG, WebP o AVIF.");
    }

    try {
        $resultado = clienteCloudinary()->uploadApi()->upload($archivoTemporal, [
            'folder' => 'dinastiaamv/planes',
            'resource_type' => 'image',
            'use_filename' => false,
            'unique_filename' => true,
            'overwrite' => false,
            'tags' => ['dinastiaamv', 'plan_turistico'],
        ]);
    } catch (Throwable $error) {
        error_log('Error al subir imagen a Cloudinary: ' . $error->getMessage());
        throw new RuntimeException('No fue posible subir una imagen a Cloudinary.');
    }

    $url = (string) ($resultado['secure_url'] ?? '');
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new RuntimeException('Cloudinary no devolvió una URL segura para la imagen.');
    }

    return $url;
}
