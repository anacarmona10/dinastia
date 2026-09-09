<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';


$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

function enviarCorreoVerificacion($destinatario, $codigo)
{
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP de Gmail
        $mail->isSMTP();
        $mail->Host = $_ENV['MAIL_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['MAIL_USERNAME'];
        $mail->Password = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['MAIL_PORT'];

        // Remitente
        $mail->setFrom(
            $_ENV['MAIL_FROM'],
            $_ENV['MAIL_FROM_NAME']
        );

        // Destinatario
        $mail->addAddress($destinatario);

        // Contenido del correo
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Código de verificación - DINASTIA AMV';

        $mail->Body = "
            <h2>Verificación de correo</h2>
            <p>Hola,</p>
            <p>Gracias por registrarte en <strong>DINASTIA AMV</strong>.</p>
            <p>Tu código de verificación es:</p>
            <h1 style='letter-spacing: 8px;'>$codigo</h1>
            <p>Este código es válido durante 10 minutos.</p>
            <p>Si no realizaste este registro, puedes ignorar este correo.</p>
        ";

        $mail->AltBody = "Tu código de verificación para DINASTIA AMV es: $codigo. Este código es válido durante 10 minutos.";

        $mail->send();

        return true;

    } catch (Exception $e) {
        return false;
    }
}


// Provisional

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $destinatario = $_POST['correo'] ?? '';

    if (empty($destinatario)) {
        echo "Falta el correo.";
        exit;
    }

    $codigoPrueba = '123456';

    if (enviarCorreoVerificacion($destinatario, $codigoPrueba)) {
        echo "Correo enviado correctamente.";
    } else {
        echo "No se pudo enviar el correo.";
    }
}