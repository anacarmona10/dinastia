<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/conexion.php';
header('Content-Type: application/json; charset=utf-8');
function responderVerificacion(int $estado, array $respuesta): never { http_response_code($estado); echo json_encode($respuesta, JSON_UNESCAPED_UNICODE); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') responderVerificacion(405, ['success' => false, 'error' => 'Método no permitido.']);
$registro = $_SESSION['registro_pendiente'] ?? null;
$codigo = trim((string) ($_POST['codigo'] ?? ''));
if (!is_array($registro) || empty($_SESSION['registro_codigo_hash'])) responderVerificacion(401, ['success' => false, 'error' => 'El registro pendiente ya no existe. Regístrate nuevamente.']);
if (!preg_match('/^\d{6}$/', $codigo)) responderVerificacion(400, ['success' => false, 'error' => 'El código debe tener seis dígitos.']);
if (time() > (int) ($_SESSION['registro_codigo_expira'] ?? 0)) responderVerificacion(410, ['success' => false, 'error' => 'El código venció. Solicita uno nuevo.']);
$intentos = (int) ($_SESSION['registro_intentos'] ?? 0);
if ($intentos >= 5) { unset($_SESSION['registro_pendiente'], $_SESSION['registro_codigo_hash'], $_SESSION['registro_codigo_expira'], $_SESSION['registro_ultimo_reenvio'], $_SESSION['registro_intentos']); responderVerificacion(429, ['success' => false, 'error' => 'Superaste el límite de intentos. Regístrate nuevamente.']); }
if (!password_verify($codigo, $_SESSION['registro_codigo_hash'])) { $_SESSION['registro_intentos'] = $intentos + 1; responderVerificacion(400, ['success' => false, 'error' => 'El código no es correcto.']); }
try {
    $pdo->beginTransaction();
    $verificar = $pdo->prepare('SELECT id FROM usuarios WHERE correo = ? FOR UPDATE');
    $verificar->execute([$registro['correo']]);
    if ($verificar->fetch()) { $pdo->rollBack(); responderVerificacion(409, ['success' => false, 'error' => 'El correo ya está registrado.']); }
    $insertar = $pdo->prepare('INSERT INTO usuarios ("nombreCompleto", "tipoDocumento", "numeroDocumento", correo, contraseña, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
    $insertar->execute([$registro['nombreCompleto'], $registro['tipoDocumento'], $registro['numeroDocumento'], $registro['correo'], $registro['contrasena_hash']]);
    $pdo->commit();
    unset($_SESSION['registro_pendiente'], $_SESSION['registro_codigo_hash'], $_SESSION['registro_codigo_expira'], $_SESSION['registro_ultimo_reenvio'], $_SESSION['registro_intentos']);
    responderVerificacion(201, ['success' => true, 'message' => 'Correo verificado. Ya puedes iniciar sesión.']);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Error verificando código de registro: ' . $error->getMessage());
    responderVerificacion(500, ['success' => false, 'error' => 'No fue posible completar el registro.']);
}
