<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/enviar_correo.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success' => false, 'error' => 'Método no permitido.']); exit; }
$registro = $_SESSION['registro_pendiente'] ?? null;
if (!is_array($registro) || empty($registro['correo'])) { http_response_code(401); echo json_encode(['success' => false, 'error' => 'El registro pendiente ya no existe.']); exit; }
$segundosRestantes = 60 - (time() - (int) ($_SESSION['registro_ultimo_reenvio'] ?? 0));
if ($segundosRestantes > 0) { http_response_code(429); echo json_encode(['success' => false, 'error' => "Espera {$segundosRestantes} segundos para reenviar el código."], JSON_UNESCAPED_UNICODE); exit; }
$codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
if (!enviarCorreoVerificacion($registro['correo'], $registro['nombreCompleto'], $codigo)) { http_response_code(502); echo json_encode(['success' => false, 'error' => 'No fue posible enviar el correo. Inténtalo más tarde.'], JSON_UNESCAPED_UNICODE); exit; }
$_SESSION['registro_codigo_hash'] = password_hash($codigo, PASSWORD_DEFAULT);
$_SESSION['registro_codigo_expira'] = time() + 600;
$_SESSION['registro_ultimo_reenvio'] = time();
$_SESSION['registro_intentos'] = 0;
echo json_encode(['success' => true, 'message' => 'Enviamos un nuevo código a tu correo.'], JSON_UNESCAPED_UNICODE);
