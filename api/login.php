<?php
// api/login.php
session_start();
require_once 'conexion.php'; // tu conexión PDO

// Verificar que la petición sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../Login.html');
    exit;
}

// Recibir datos del formulario
$correo = trim($_POST['correo'] ?? '');
$contraseña = $_POST['contraseña'] ?? '';
$tipo_usuario = $_POST['tipo_usuario'] ?? 'usuario';

$errores = [];

// Validaciones básicas
if (empty($correo)) {
    $errores[] = "El correo electrónico es obligatorio";
} elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $errores[] = "El correo no tiene un formato válido";
}
if (empty($contraseña)) {
    $errores[] = "La contraseña es obligatoria";
}

// Si hay errores, mostrarlos y detener
if (!empty($errores)) {
    mostrarErrores($errores);
    exit;
}

// Buscar el usuario por correo
$stmt = $pdo->prepare("SELECT id, nombreCompleto, correo, contraseña, tipoDocumento, numeroDocumento FROM users WHERE correo = ?");
$stmt->execute([$correo]);
$usuario = $stmt->fetch();

// Verificar si existe y si la contraseña es correcta
if (!$usuario || !password_verify($contraseña, $usuario['contraseña'])) {
    $errores[] = "Correo o contraseña incorrectos";
    mostrarErrores($errores);
    exit;
}

// Si todo es correcto, iniciar sesión
$_SESSION['user_id'] = $usuario['id'];
$_SESSION['user_nombre'] = $usuario['nombreCompleto'];
$_SESSION['user_email'] = $usuario['correo'];
$_SESSION['user_tipo_doc'] = $usuario['tipoDocumento'];
$_SESSION['user_num_doc'] = $usuario['numeroDocumento'];
$_SESSION['tipo_usuario'] = $tipo_usuario;
$_SESSION['logged_in'] = true;

// Redirigir según el tipo de usuario
if ($tipo_usuario === 'admin') {
    header('Location: ../InterfazAdmin.html');
} else {
    header('Location: ../index.html');
}
exit;

// Función auxiliar para mostrar errores en una página amigable
function mostrarErrores($errores) {
    echo "<!DOCTYPE html>";
    echo "<html><head><meta charset='UTF-8'><title>Error de inicio de sesión</title>";
    echo "<link rel='stylesheet' href='../style.css'>";
    echo "</head><body>";
    echo "<div class='mensaje-error' style='max-width:500px; margin:50px auto; padding:20px; background:#ffe6e5; border-radius:1rem;'>";
    echo "<h3>No se pudo iniciar sesión</h3><ul>";
    foreach ($errores as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul><a href='../Login.html'>← Volver al inicio de sesión</a>";
    echo "</div></body></html>";
}
?>