<?php
// registrar_usuario.php
session_start();
require_once 'conexion.php'; // conexión PDO

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreCompleto = trim($_POST['nombreCompleto'] ?? '');
    $tipoDocumento = trim($_POST['tipoDocumento'] ?? '');
    $numeroDocumento = trim($_POST['numeroDocumento'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $contraseña = $_POST['contraseña'] ?? '';
    $confirmar = $_POST['confirmar_contraseña'] ?? '';

    $errores = [];
    if (empty($nombreCompleto)) $errores[] = "El nombre es obligatorio";
    if (empty($tipoDocumento)) $errores[] = "Seleccione un tipo de documento";
    if (empty($numeroDocumento)) $errores[] = "El número de documento es obligatorio";
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $errores[] = "Correo electrónico inválido";
    if (strlen($contraseña) < 6) $errores[] = "La contraseña debe tener al menos 6 caracteres";
    if ($contraseña !== $confirmar) $errores[] = "Las contraseñas no coinciden";

    if (empty($errores)) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        if ($stmt->fetch()) {
            $errores[] = "El correo electrónico ya está registrado";
        }
    }

    if (empty($errores)) {
        $hash = password_hash($contraseña, PASSWORD_DEFAULT);
        $sql = "INSERT INTO usuarios (nombreCompleto, tipoDocumento, numeroDocumento, correo, contraseña, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        try {
            $stmt->execute([$nombreCompleto, $tipoDocumento, $numeroDocumento, $correo, $hash]);
            // Mostrar mensaje de éxito
            echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Registro exitoso</title>";
            echo "<style>body{font-family:Arial;text-align:center;padding:50px;background:#f0f9f0;}h1{color:#2e7d32;}a{color:#c800ff;}</style>";
            echo "</head><body>";
            echo "<h1>✅ ¡Registro exitoso!</h1>";
            echo "<p>Ya puedes <a href='../login.html'>iniciar sesión</a>.</p>";
            echo "</body></html>";
            exit;
        } catch(PDOException $e) {
            $errores[] = "Error al guardar: " . $e->getMessage();
        }
    }

    // Si hay errores, mostrar mensaje de error
    if (!empty($errores)) {
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Error en registro</title>";
        echo "<style>body{font-family:Arial;text-align:center;padding:50px;background:#fff5f5;}h3{color:#c62828;}ul{text-align:left;display:inline-block;}a{color:#c800ff;}</style>";
        echo "</head><body>";
        echo "<h3>❌ No se pudo completar el registro:</h3><ul>";
        foreach ($errores as $err) {
            echo "<li>$err</li>";
        }
        echo "</ul><a href='javascript:history.back()'>← Volver al formulario</a>";
        echo "</body></html>";
        exit;
    }
} else {
    header("Location: index.html");
    exit;
}
?>