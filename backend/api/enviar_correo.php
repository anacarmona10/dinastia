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

function enviarCorreoConfirmacionPago(array $pago): bool
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = correoConfig('MAIL_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = correoConfig('MAIL_USERNAME');
        $mail->Password = correoConfig('MAIL_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int) correoConfig('MAIL_PORT', '587');

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

        $mail->send();

        return true;
    } catch (Exception $error) {
        error_log('Error enviando correo de pago: ' . $error->getMessage());
        return false;
    }
}