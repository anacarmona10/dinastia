<?php
// ============================================================
// INICIO DE SESIÓN CON GOOGLE - OAuth2 (CON INTERFAZ VISUAL)
// ============================================================
// Coloca este archivo en: backend/api/auth/google.php
// Asegúrate de que la conexión a BD esté configurada en conexion.php
// ============================================================

session_start();

// Incluir la conexión a la base de datos
require_once '../conexion.php'; // o la ruta correcta

// ============================================================
// DEFINIR BASE_PATH PARA RUTAS ABSOLUTAS
// ============================================================
// Si tu proyecto está en la raíz del servidor (ej: http://localhost:8080/)
// BASE_PATH debe ser '/'
// Si está en una subcarpeta (ej: http://localhost:8080/mi-proyecto/)
// entonces BASE_PATH = '/mi-proyecto/'
// ============================================================
$BASE_PATH = '/'; // Ajusta según tu estructura

// ============================================================
// CONFIGURACIÓN DE GOOGLE (DEBES REEMPLAZAR CON TUS DATOS)
// ============================================================
const GOOGLE_CLIENT_ID     = 'TU_CLIENT_ID.apps.googleusercontent.com';
const GOOGLE_CLIENT_SECRET = 'TU_CLIENT_SECRET';
const GOOGLE_REDIRECT_URI  = 'http://localhost:8080/backend/api/auth/google.php'; // ¡Ajusta a tu URL real!

// ============================================================
// FUNCIÓN PARA GENERAR URL DE AUTORIZACIÓN DE GOOGLE
// ============================================================
function getGoogleAuthUrl() {
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'email profile',
        'access_type'   => 'offline',
        'prompt'        => 'consent'
    ]);
}

// ============================================================
// FUNCIONES DE AUTENTICACIÓN (callback)
// ============================================================
function getGoogleToken($code) {
    $url = 'https://oauth2.googleapis.com/token';
    $data = [
        'code'          => $code,
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $response = curl_exec($ch);
    curl_close($ch);

    $token = json_decode($response, true);
    if (isset($token['error'])) {
        die('Error de Google: ' . $token['error_description'] ?? 'Desconocido');
    }
    return $token;
}

function getGoogleUser($access_token) {
    $url = 'https://www.googleapis.com/oauth2/v2/userinfo';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
    $response = curl_exec($ch);
    curl_close($ch);

    $user = json_decode($response, true);
    if (isset($user['error'])) {
        die('Error al obtener perfil: ' . $user['error']['message'] ?? 'Desconocido');
    }
    return $user;
}

function loginOrRegisterUser($googleUser) {
    global $pdo;
    if (!$pdo instanceof PDO) {
        throw new RuntimeException('La conexión a la base de datos no está disponible.');
    }

    $stmt = $pdo->prepare("SELECT id, nombreCompleto, correo, tipo_usuario FROM usuarios WHERE google_id = ? OR correo = ?");
    $stmt->execute([$googleUser['id'], $googleUser['email']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        $nombreCompleto = $googleUser['name'] ?? $googleUser['given_name'] . ' ' . ($googleUser['family_name'] ?? '');
        $correo = $googleUser['email'];

        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombreCompleto, correo, google_id, tipo_usuario, verificado)
            VALUES (?, ?, ?, 'usuario', 1)
        ");
        $stmt->execute([$nombreCompleto, $correo, $googleUser['id']]);
        $usuarioId = $pdo->lastInsertId();
        $usuario = [
            'id' => $usuarioId,
            'nombreCompleto' => $nombreCompleto,
            'correo' => $correo,
            'tipo_usuario' => 'usuario'
        ];
    }

    $_SESSION['user_id'] = $usuario['id'];
    $_SESSION['user_nombre'] = $usuario['nombreCompleto'];
    $_SESSION['user_email'] = $usuario['correo'];
    $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'] ?? 'usuario';
    $_SESSION['logged_in'] = true;

    if (($_SESSION['tipo_usuario'] ?? '') === 'admin') {
        header('Location: ../../frontend/interfazAdmin.php');
    } else {
        header('Location: ../../frontend/dashboard.html');
    }
    exit;
}

// ============================================================
// FLUJO PRINCIPAL
// ============================================================

// Si hay código (callback de Google), procesar la autenticación
if (isset($_GET['code'])) {
    $token = getGoogleToken($_GET['code']);
    $access_token = $token['access_token'] ?? null;
    if (!$access_token) {
        die('No se pudo obtener el token de acceso.');
    }
    $googleUser = getGoogleUser($access_token);
    if (empty($googleUser['email'])) {
        die('No se pudo obtener el correo electrónico.');
    }
    loginOrRegisterUser($googleUser);
    exit;
}

// Si no hay código, mostrar página de inicio de sesión con Google
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión con Google - Dinastía AMV</title>

    <!-- Font Awesome para íconos adicionales (opcional) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        /* ==========================================================
           ESTILOS (idénticos a login.html, con fondo de iglesia)
           ========================================================== */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: linear-gradient(rgba(0, 0, 0, 0.35), rgba(0, 0, 0, 0.35)),
                        url("<?php echo $BASE_PATH; ?>frontend/imagenes/Iglesia.jpg") no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
        }

        .google-container {
            background: #ffffff;
            padding: 40px 35px;
            border-radius: 16px;
            width: 380px;
            max-width: 90%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            text-align: center;
        }

        .google-container h2 {
            color: #000;
            margin-bottom: 10px;
            font-size: 1.8rem;
        }

        .google-container .subtitulo {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 30px;
            line-height: 1.4;
        }

        .icono-google {
            /* Eliminamos el círculo con el ícono de Font Awesome, ahora usamos SVG */
            display: none;
        }

        /* Nuevo contenedor para el logo SVG de Google */
        .logo-google-svg {
            display: flex;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .logo-google-svg svg {
            width: 56px;
            height: 56px;
            display: block;
        }

        .btn-google {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            width: 100%;
            padding: 0.85rem 1.2rem;
            background: #ffffff;
            border: 2px solid #d0d0d0;
            border-radius: 12px;
            color: #3c4043;
            font-weight: 700;
            font-size: 1.05rem;
            cursor: pointer;
            transition: all 0.25s ease;
            text-decoration: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
            margin-top: 10px;
        }
        .btn-google:hover {
            background: #f8f9fa;
            border-color: #b0b0b0;
            transform: scale(1.02);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }
        /* El SVG del logo de Google dentro del botón */
        .btn-google .google-logo-btn {
            width: 22px;
            height: 22px;
            flex-shrink: 0;
        }
        .btn-google span {
            font-weight: 700;
        }

        .separador-o {
            display: flex;
            align-items: center;
            margin: 20px 0;
            color: #aaa;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .separador-o::before,
        .separador-o::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #e0e0e0;
        }
        .separador-o::before {
            margin-right: 1rem;
        }
        .separador-o::after {
            margin-left: 1rem;
        }

        .registro {
            margin-top: 20px;
            color: #333;
            font-size: 0.9rem;
        }
        .registro a {
            color: #c800ff;
            font-weight: bold;
            text-decoration: none;
        }
        .registro a:hover {
            color: #EF1D9C;
        }

        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 10;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(6px);
            border-radius: 50%;
            color: white;
            font-size: 20px;
            text-decoration: none;
            transition: 0.3s ease;
        }
        .back-button:hover {
            background: rgba(255, 255, 255, 0.35);
            transform: scale(1.08);
        }

        @media (max-width: 480px) {
            .google-container {
                padding: 30px 20px;
            }
            .google-container h2 {
                font-size: 1.5rem;
            }
            .btn-google {
                font-size: 0.95rem;
                padding: 0.7rem 1rem;
            }
            .btn-google .google-logo-btn {
                width: 18px;
                height: 18px;
            }
        }
    </style>
</head>
<body>

    <!-- Botón de volver (con ruta absoluta) -->
    <a href="<?php echo $BASE_PATH; ?>frontend/login.html" class="back-button" aria-label="Volver al inicio de sesión">
        <i class="fas fa-arrow-left"></i>
    </a>

    <div class="google-container">

        <!-- Logo de Google con colores (SVG) -->
        <div class="logo-google-svg">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z" />
                <path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z" />
                <path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z" />
                <path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z" />
            </svg>
        </div>

        <h2>Inicio con Google</h2>
        <p class="subtitulo">Accede a tu cuenta de Dinastía AMV usando tu cuenta de Google.</p>

        <!-- Botón con logo SVG de Google a colores -->
        <a href="<?php echo getGoogleAuthUrl(); ?>" class="btn-google">
            <svg class="google-logo-btn" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z" />
                <path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z" />
                <path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z" />
                <path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z" />
            </svg>
            <span>Continuar con Google</span>
        </a>

        <div class="separador-o">o</div>

        <p class="registro">
            ¿Prefieres usar correo y contraseña?
            <a href="<?php echo $BASE_PATH; ?>frontend/login.html">Inicia sesión aquí</a>
        </p>
        <p class="registro">
            ¿No tienes cuenta?
            <a href="<?php echo $BASE_PATH; ?>frontend/Registro.html">Regístrate</a>
        </p>
    </div>

</body>
</html>