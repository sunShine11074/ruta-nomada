<?php
// ============================================================
//  mailer.php — Helper de envío de correo (PHPMailer) | Ruta Nómada
// ------------------------------------------------------------
//  PHPMailer instalado de forma manual en libs/PHPMailer/
//  (sin Composer). Carga los tres archivos requeridos.
// ============================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/libs/PHPMailer/Exception.php';
require_once __DIR__ . '/libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/libs/PHPMailer/SMTP.php';

if (!function_exists('mailConfig')) {
    function mailConfig(): array
    {
        static $cfg;
        if ($cfg === null) {
            $cfg = require __DIR__ . '/includes/mail_config.php';
        }
        return $cfg;
    }
}

if (!function_exists('enviarCorreo')) {
    /**
     * Envío genérico de correo con la plantilla de la marca
     * (cabecera con logo + cuerpo HTML). Devuelve true/false.
     */
    function enviarCorreo(string $toEmail, string $subject, string $htmlInner, string $altBody = ''): bool
    {
        $cfg = mailConfig();

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $cfg['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $cfg['username'];
            $mail->Password   = $cfg['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) $cfg['port'];
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($cfg['from_addr'], $cfg['from_name']);
            $mail->addAddress($toEmail);

            $logoPath = __DIR__ . '/img/logo.png';
            $logoTag  = '';
            if (is_file($logoPath)) {
                $mail->addEmbeddedImage($logoPath, 'logo_rn', 'logo.png');
                $logoTag = '<img src="cid:logo_rn" width="28" height="28" alt="" '
                    . 'style="display:inline-block;vertical-align:middle;">';
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = <<<HTML
<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:0 auto;color:#1f2d2b;">
  <div style="background:#1b3a40;color:#fff;padding:24px;border-radius:12px 12px 0 0;">
    {$logoTag}<span style="font-size:20px;font-weight:700;vertical-align:middle;margin-left:11px;">Ruta Nómada</span>
  </div>
  <div style="background:#f7f9f7;padding:28px;border-radius:0 0 12px 12px;">
    {$htmlInner}
  </div>
</div>
HTML;
            $mail->AltBody = $altBody !== '' ? $altBody : strip_tags($htmlInner);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('[Ruta Nomada] Error al enviar correo: ' . $mail->ErrorInfo);
            return false;
        }
    }
}

if (!function_exists('enviarCorreoRecuperacion')) {
    /**
     * Envía el correo con el enlace de recuperación de contraseña.
     * Devuelve true si el correo se envió correctamente, false en caso de error.
     */
    function enviarCorreoRecuperacion(string $toEmail, string $toName, string $resetLink): bool
    {
        $cfg = mailConfig();

        $mail = new PHPMailer(true);
        try {
            // ── Servidor SMTP ──
            $mail->isSMTP();
            $mail->Host       = $cfg['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $cfg['username'];
            $mail->Password   = $cfg['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) $cfg['port'];
            $mail->CharSet    = 'UTF-8';

            // ── Remitente / destinatario ──
            $mail->setFrom($cfg['from_addr'], $cfg['from_name']);
            $mail->addAddress($toEmail, $toName);

            // ── Logo embebido (CID) — visible aunque el cliente bloquee imágenes externas ──
            $logoPath = __DIR__ . '/img/logo.png';
            $logoTag  = '';
            if (is_file($logoPath)) {
                $mail->addEmbeddedImage($logoPath, 'logo_rn', 'logo.png');
                $logoTag = '<img src="cid:logo_rn" width="28" height="28" alt="" '
                    . 'style="display:inline-block;vertical-align:middle;">';
            }

            // ── Contenido ──
            $mail->isHTML(true);
            $mail->Subject = 'Restablece tu contraseña — Ruta Nómada';

            $safeName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
            $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');

            $mail->Body = <<<HTML
<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:0 auto;color:#1f2d2b;">
  <div style="background:#1b3a40;color:#fff;padding:24px;border-radius:12px 12px 0 0;">
    {$logoTag}<span style="font-size:20px;font-weight:700;vertical-align:middle;margin-left:11px;">Ruta Nómada</span>
  </div>
  <div style="background:#f7f9f7;padding:28px;border-radius:0 0 12px 12px;">
    <h2 style="margin-top:0;">Hola, {$safeName}</h2>
    <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta.
       Haz clic en el botón para crear una nueva contraseña:</p>
    <p style="text-align:center;margin:28px 0;">
      <a href="{$safeLink}"
         style="background:#1b3a40;color:#fff;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:600;display:inline-block;">
        Restablecer contraseña
      </a>
    </p>
    <p style="font-size:13px;color:#5b6b68;">
      Este enlace caduca en <strong>1 hora</strong> y solo puede usarse una vez.
      Si no solicitaste esto, puedes ignorar este correo; tu contraseña no cambiará.
    </p>
    <p style="font-size:12px;color:#8a9794;word-break:break-all;">
      Si el botón no funciona, copia y pega esta dirección en tu navegador:<br>{$safeLink}
    </p>
  </div>
</div>
HTML;
            $mail->AltBody = "Hola {$toName},\n\n"
                . "Para restablecer tu contraseña abre este enlace (caduca en 1 hora):\n"
                . "{$resetLink}\n\n"
                . "Si no solicitaste esto, ignora este correo.";

            $mail->send();
            return true;
        } catch (Exception $e) {
            // No exponemos el detalle al usuario; lo registramos para depuración.
            error_log('[Ruta Nomada] Error al enviar correo de recuperación: ' . $mail->ErrorInfo);
            return false;
        }
    }
}
