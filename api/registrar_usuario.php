<?php
// registrar_usuario.php

session_start();
require_once 'conexion.php'; // tu conexión PDO

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Verificar que los datos llegaron por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger y limpiar datos
    $nombreCompleto = trim($_POST['nombreCompleto'] ?? '');
    $tipoDocumento = trim($_POST['tipoDocumento'] ?? '');
    $numeroDocumento = trim($_POST['numeroDocumento'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $contraseña = $_POST['contraseña'] ?? '';
    $confirmar = $_POST['confirmar_contraseña'] ?? '';

    // Validaciones básicas
    $errores = [];
    if (empty($nombreCompleto)) $errores[] = "El nombre es obligatorio";
    if (empty($tipoDocumento)) $errores[] = "Seleccione un tipo de documento";
    if (empty($numeroDocumento)) $errores[] = "El número de documento es obligatorio";
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $errores[] = "Correo electrónico inválido";
    if (strlen($contraseña) < 6) $errores[] = "La contraseña debe tener al menos 6 caracteres";
    if ($contraseña !== $confirmar) $errores[] = "Las contraseñas no coinciden";

    // Verificar si el correo ya existe en la BD
    if (empty($errores)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE correo = ?");
        $stmt->execute([$correo]);
        if ($stmt->fetch()) {
            $errores[] = "El correo electrónico ya está registrado";
        }
    }

    // Si no hay errores, insertar
    if (empty($errores)) {
        // Hashear la contraseña
        $hash = password_hash($contraseña, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (nombreCompleto, tipoDocumento, numeroDocumento, correo, contraseña, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        try {
            $stmt->execute([$nombreCompleto, $tipoDocumento, $numeroDocumento, $correo, $hash]);
            // Redirigir a una página de éxito o mostrar mensaje
            echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Registro exitoso</title></head><body>
                  <h3>¡Registro exitoso!</h3>
                  <p>Ya puedes <a href='../Login.html'>iniciar sesión</a>.</p>
                  </body></html>";
            exit;
        } catch(PDOException $e) {
            $errores[] = "Error al guardar: " . $e->getMessage();
        }
    }

    // Si hay errores, mostrarlos y volver al formulario
    if (!empty($errores)) {
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Error en registro</title></head><body>";
        echo "<h3>No se pudo completar el registro:</h3><ul>";
        foreach ($errores as $err) {
            echo "<li>$err</li>";
        }
        echo "</ul><a href='javascript:history.back()'>Volver al formulario</a>";
        echo "</body></html>";
        exit;
    }
} else {
    // Si alguien accede directamente al script sin POST
    header("Location: index.html");
    exit;
}
?>