<?php
// registrar_usuario.php
session_start();
require_once 'conexion.php'; // conexión PDO
require_once 'enviar_correo.php';

function verificarRecaptcha(string $token): bool {
    $secret = variableEntorno('RECAPTCHA_SECRET_KEY');

    if ($secret === '' || $token === '') {
        return false;
    }

    $contexto = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query([
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]),
            'timeout' => 5,
        ],
    ]);

    $respuesta = @file_get_contents(
        'https://www.recaptcha.net/recaptcha/api/siteverify',
        false,
        $contexto
    );
    $resultado = is_string($respuesta) ? json_decode($respuesta, true) : null;

    if (!is_array($resultado) || empty($resultado['success'])) {
        return false;
    }

    $hostnameEsperado = variableEntorno('RECAPTCHA_EXPECTED_HOSTNAME');
    if ($hostnameEsperado !== '' && ($resultado['hostname'] ?? '') !== $hostnameEsperado) {
        return false;
    }

    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreCompleto = trim($_POST['nombreCompleto'] ?? '');
    $tipoDocumento = trim($_POST['tipoDocumento'] ?? '');
    $numeroDocumento = trim($_POST['numeroDocumento'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $contraseña = $_POST['contraseña'] ?? '';
    $confirmar = $_POST['confirmar_contraseña'] ?? '';
    $tokenRecaptcha = $_POST['g-recaptcha-response'] ?? '';

    $errores = [];
    if (empty($nombreCompleto)) $errores[] = "El nombre es obligatorio";
    if (empty($tipoDocumento)) $errores[] = "Seleccione un tipo de documento";
    if (empty($numeroDocumento)) $errores[] = "El número de documento es obligatorio";
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $errores[] = "Correo electrónico inválido";
    if (strlen($contraseña) < 6) $errores[] = "La contraseña debe tener al menos 6 caracteres";
    if ($contraseña !== $confirmar) $errores[] = "Las contraseñas no coinciden";
    if (!verificarRecaptcha($tokenRecaptcha)) {
        $errores[] = "No fue posible validar reCAPTCHA. Inténtalo nuevamente.";
    }

    if (empty($errores)) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        if ($stmt->fetch()) {
            $errores[] = "El correo electrónico ya está registrado";
        }
    }

    if (empty($errores)) {
        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['registro_pendiente'] = ['nombreCompleto' => $nombreCompleto, 'tipoDocumento' => $tipoDocumento, 'numeroDocumento' => $numeroDocumento, 'correo' => $correo, 'contrasena_hash' => password_hash($contraseña, PASSWORD_DEFAULT)];
        $_SESSION['registro_codigo_hash'] = password_hash($codigo, PASSWORD_DEFAULT);
        $_SESSION['registro_codigo_expira'] = time() + 600;
        $_SESSION['registro_ultimo_reenvio'] = time();
        $_SESSION['registro_intentos'] = 0;

        if (!enviarCorreoVerificacion($correo, $nombreCompleto, $codigo)) {
            unset($_SESSION['registro_pendiente'], $_SESSION['registro_codigo_hash'], $_SESSION['registro_codigo_expira'], $_SESSION['registro_ultimo_reenvio'], $_SESSION['registro_intentos']);
            $errores[] = 'No fue posible enviar el código de verificación. Inténtalo nuevamente.';
        } else {
            header('Location: ../../frontend/verificacion.html');
            exit;
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
    header("Location: ../../frontend/index.html");
    exit;
}
?>
