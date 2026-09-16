<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

header('Content-Type: application/json; charset=utf-8');

$raizProyecto = dirname(__DIR__, 2);
if (file_exists($raizProyecto . '/.env')) {
    Dotenv::createImmutable($raizProyecto)->safeLoad();
}

$claveSitio = $_ENV['RECAPTCHA_SITE_KEY'] ?? $_SERVER['RECAPTCHA_SITE_KEY'] ?? getenv('RECAPTCHA_SITE_KEY') ?: '';

if ($claveSitio === '') {
    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'error' => 'reCAPTCHA no está configurado',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok' => true,
    'recaptchaSiteKey' => $claveSitio,
], JSON_UNESCAPED_UNICODE);
