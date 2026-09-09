<?php

require_once 'enviar_correo.php';

$correoPrueba = 'anacarmona1023@gmail.com';
$codigoPrueba = '123456';

$resultado = enviarCorreoVerificacion($correoPrueba, $codigoPrueba);

if ($resultado) {
    echo "✅ Correo enviado correctamente.";
} else {
    echo "❌ No se pudo enviar el correo.";
}