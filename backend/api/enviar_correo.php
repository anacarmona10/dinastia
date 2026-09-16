<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

function correoConfig(string $nombre, string $porDefecto = ''): string
{
    $valor = $_ENV[$nombre] ?? $_SERVER[$nombre] ?? getenv($nombre);

    return ($valor === false || $valor === null)
        ? $porDefecto
        : trim((string) $valor);
}

function enviarCorreoConResend(
    string $correo,
    string $nombre,
    string $asunto,
    string $html,
    string $texto
): bool {
    $apiKey = correoConfig('RESEND_API_KEY');
    $remitente = correoConfig('MAIL_FROM');
    $nombreRemitente = correoConfig('MAIL_FROM_NAME', 'Dinastía AMV');

    if ($apiKey === '' || $remitente === '') {
        return false;
    }

    $contenido = json_encode([
        'from' => "{$nombreRemitente} <{$remitente}>",
        'to' => [$correo],
        'subject' => $asunto,
        'html' => $html,
        'text' => $texto,
    ]);

    if ($contenido === false) {
        error_log('No fue posible preparar el correo para Resend.');
        return false;
    }

    $contexto = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Authorization: Bearer {$apiKey}\r\nContent-Type: application/json\r\n",
            'content' => $contenido,
            'timeout' => 12,
            'ignore_errors' => true,
        ],
    ]);

    $respuesta = @file_get_contents(
        'https://api.resend.com/emails',
        false,
        $contexto
    );

    $estado = 0;
    foreach ($http_response_header ?? [] as $cabecera) {
        if (preg_match('/^HTTP\\/\\S+\\s+(\\d{3})/', $cabecera, $coincidencia)) {
            $estado = (int) $coincidencia[1];
            break;
        }
    }

    if ($respuesta === false || $estado < 200 || $estado >= 300) {
        $detalle = is_string($respuesta) ? trim($respuesta) : '';
        error_log("Error enviando correo con Resend. HTTP: {$estado}. {$detalle}");
        return false;
    }

    return true;
}

function configurarSmtp(PHPMailer $mail): void
{
    $mail->isSMTP();
    $mail->Host = correoConfig('MAIL_HOST');
    $mail->SMTPAuth = true;
    $mail->Username = correoConfig('MAIL_USERNAME');
    $mail->Password = correoConfig('MAIL_PASSWORD');
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int) correoConfig('MAIL_PORT', '587');
    $mail->Timeout = 12;
}

function enviarCorreoVerificacion(string $correo, string $nombre, string $codigo): bool
{
    $nombreSeguro = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
    $codigoSeguro = htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8');
    $asunto = 'Tu código de verificación - Dinastía AMV';
    $html = "<div style='font-family:Arial,sans-serif;color:#1e293b;max-width:600px;margin:auto'><h1 style='color:#c800ff'>Verifica tu correo</h1><p>Hola <strong>{$nombreSeguro}</strong>,</p><p>Usa este código para completar tu registro en Dinastía AMV:</p><p style='font-size:32px;font-weight:bold;letter-spacing:8px;color:#c800ff'>{$codigoSeguro}</p><p>El código vence en 10 minutos. No lo compartas con nadie.</p></div>";
    $texto = "Tu código de verificación de Dinastía AMV es: {$codigo}. Vence en 10 minutos.";

    if (correoConfig('RESEND_API_KEY') !== '') {
        return enviarCorreoConResend($correo, $nombre, $asunto, $html, $texto);
    }

    $mail = new PHPMailer(true);

    try {
        configurarSmtp($mail);
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(correoConfig('MAIL_FROM'), correoConfig('MAIL_FROM_NAME', 'Dinastía AMV'));
        $mail->addAddress($correo, $nombre);
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $html;
        $mail->AltBody = $texto;
        $mail->send();
        return true;
    } catch (Exception $error) {
        error_log('Error enviando correo de verificación: ' . $error->getMessage());
        return false;
    }
}

function enviarCorreoRecuperacion(string $correo, string $nombre, string $codigo): bool
{
    $nombreSeguro = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
    $codigoSeguro = htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8');
    $asunto = 'Código para restablecer tu contraseña - Dinastía AMV';
    $html = "<div style='font-family:Arial,sans-serif;color:#1e293b;max-width:600px;margin:auto'><h1 style='color:#c800ff'>Restablece tu contraseña</h1><p>Hola <strong>{$nombreSeguro}</strong>,</p><p>Usa este código para crear una nueva contraseña en Dinastía AMV:</p><p style='font-size:32px;font-weight:bold;letter-spacing:8px;color:#c800ff'>{$codigoSeguro}</p><p>El código vence en 10 minutos. Si no solicitaste este cambio, puedes ignorar este correo.</p></div>";
    $texto = "Tu código para restablecer la contraseña de Dinastía AMV es: {$codigo}. Vence en 10 minutos.";

    if (correoConfig('RESEND_API_KEY') !== '') {
        return enviarCorreoConResend($correo, $nombre, $asunto, $html, $texto);
    }

    $mail = new PHPMailer(true);

    try {
        configurarSmtp($mail);
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(correoConfig('MAIL_FROM'), correoConfig('MAIL_FROM_NAME', 'Dinastía AMV'));
        $mail->addAddress($correo, $nombre);
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $html;
        $mail->AltBody = $texto;
        $mail->send();

        return true;
    } catch (Exception $error) {
        error_log('Error enviando correo de recuperación: ' . $error->getMessage());
        return false;
    }
}

function enviarCorreoConfirmacionPago(array $pago): bool
{
    $mail = new PHPMailer(true);

    try {
        configurarSmtp($mail);

        $mail->setFrom(
            correoConfig('MAIL_FROM'),
            correoConfig('MAIL_FROM_NAME', 'Dinastía AMV')
        );

        $mail->addAddress($pago['correo'], $pago['nombre']);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';

        $monto = number_format(
            ((int) $pago['monto_centavos']) / 100,
            0,
            ',',
            '.'
        );

        $nombre = htmlspecialchars($pago['nombre'], ENT_QUOTES, 'UTF-8');
        $destino = htmlspecialchars($pago['destino'], ENT_QUOTES, 'UTF-8');
        $referencia = htmlspecialchars($pago['referencia'], ENT_QUOTES, 'UTF-8');
        $cantidad = (int) $pago['cantidad_personas'];

        $mail->Subject = 'Pago confirmado - Dinastía AMV';

        $mail->Body = "
          <div style='font-family:Arial,sans-serif;color:#1e293b;max-width:600px;margin:auto'>
            <h1 style='color:#c800ff'>¡Pago confirmado!</h1>

            <p>Hola <strong>{$nombre}</strong>,</p>

            <p>Tu reserva fue confirmada correctamente.</p>

            <table style='width:100%;border-collapse:collapse;margin:20px 0'>
              <tr>
                <td style='padding:10px;border-bottom:1px solid #e2e8f0'>Destino</td>
                <td style='padding:10px;border-bottom:1px solid #e2e8f0'><strong>{$destino}</strong></td>
              </tr>
              <tr>
                <td style='padding:10px;border-bottom:1px solid #e2e8f0'>Viajeros</td>
                <td style='padding:10px;border-bottom:1px solid #e2e8f0'><strong>{$cantidad}</strong></td>
              </tr>
              <tr>
                <td style='padding:10px;border-bottom:1px solid #e2e8f0'>Total pagado</td>
                <td style='padding:10px;border-bottom:1px solid #e2e8f0'><strong>\${$monto} COP</strong></td>
              </tr>
              <tr>
                <td style='padding:10px'>Referencia</td>
                <td style='padding:10px'><strong>{$referencia}</strong></td>
              </tr>
            </table>

            <p>Gracias por viajar con <strong>Dinastía AMV</strong>.</p>
          </div>
        ";

        $mail->AltBody =
            "Pago confirmado.\n" .
            "Destino: {$pago['destino']}\n" .
            "Viajeros: {$cantidad}\n" .
            "Total: \${$monto} COP\n" .
            "Referencia: {$pago['referencia']}";

        if (correoConfig('RESEND_API_KEY') !== '') {
            return enviarCorreoConResend(
                $pago['correo'],
                $pago['nombre'],
                $mail->Subject,
                $mail->Body,
                $mail->AltBody
            );
        }

        $mail->send();

        return true;
    } catch (Exception $error) {
        error_log('Error enviando correo de pago: ' . $error->getMessage());
        return false;
    }
}
