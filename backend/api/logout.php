<?php
// Cierra la sesión, sirve tanto para usuarios como para administradores
session_start();

$era_admin = ($_SESSION['tipo_usuario'] ?? '') === 'admin';

$_SESSION = [];
session_destroy();

if ($era_admin) {
    header('Location: ../../frontend/login_admin.html');
} else {
    header('Location: ../../frontend/login.html');
}
exit;