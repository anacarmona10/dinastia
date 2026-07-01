<?php
// api/login_admin.php
// Login SOLO para administradores (tabla admins)
// Nunca consulta la tabla usuarios, así ningún usuario normal puede entrar por aquí.
session_start();
require_once 'conexion.php'; // conexión PDO

// Verificar que la petición sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login_admin.html');
    exit;
}

// Recibir datos del formulario
$correo = trim($_POST['correo'] ?? '');
$contraseña = $_POST['contraseña'] ?? '';

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

if (!empty($errores)) {
    mostrarErrores($errores);
    exit;
}

// Buscar el administrador por correo (SOLO en la tabla admins)
$stmt = $pdo->prepare("SELECT id, nombreCompleto, correo, contraseña, tipoDocumento, numeroDocumento FROM admins WHERE correo = ?");
$stmt->execute([$correo]);
$admin = $stmt->fetch();

// Verificar si existe y si la contraseña es correcta
if (!$admin || !password_verify($contraseña, $admin['contraseña'])) {
    $errores[] = "Correo o contraseña incorrectos";
    mostrarErrores($errores);
    exit;
}

// Si todo es correcto, iniciar sesión de ADMIN
session_regenerate_id(true);
$_SESSION['admin_id'] = $admin['id'];
$_SESSION['admin_nombre'] = $admin['nombreCompleto'];
$_SESSION['admin_email'] = $admin['correo'];
$_SESSION['tipo_usuario'] = 'admin';
$_SESSION['logged_in'] = true;

// Los administradores siempre van a la interfaz de administración
header('Location: ../InterfazAdmin.php');
exit;

// Función auxiliar para mostrar errores en una página amigable
function mostrarErrores($errores) {
    echo "<!DOCTYPE html>";
    echo "<html><head><meta charset='UTF-8'><title>Error de inicio de sesión</title>";
    echo "<link rel='stylesheet' href='../style_login.css'>";
    echo "</head><body>";
    echo "<div class='mensaje-error' style='max-width:500px; margin:50px auto; padding:20px; background:#ffe6e5; border-radius:1rem;'>";
    echo "<h3>No se pudo iniciar sesión</h3><ul>";
    foreach ($errores as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul><a href='../login_admin.html'>← Volver al inicio de sesión</a>";
    echo "</div></body></html>";
}