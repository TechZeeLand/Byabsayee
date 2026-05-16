<?php

namespace App\Helpers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;

class Mailer
{
    /**
     * Send an email.
     *
     * @param  string|array  $to      Email address or ['email'=>..., 'name'=>...]
     * @param  string        $subject
     * @param  string        $html    HTML body
     * @param  string|null   $text    Plain-text fallback (auto-stripped if null)
     * @return bool
     * @throws \RuntimeException on fatal config error
     */
    public static function send(
        string|array $to,
        string $subject,
        string $html,
        ?string $text = null
    ): bool {
        $mail = new PHPMailer(true);

        try {
            // ── Server config ──────────────────────────────────────────────
            $host = getenv('SMTP_HOST') ?: '';
            $user = getenv('SMTP_USER') ?: '';
            $pass = getenv('SMTP_PASS') ?: '';
            $port = (int)(getenv('SMTP_PORT') ?: 587);
            $from = getenv('SMTP_FROM') ?: $user;
            $fromName = getenv('SMTP_FROM_NAME') ?: (getenv('APP_NAME') ?: 'Byabsayee');

            if (!$host || !$user || !$pass) {
                // SMTP not configured — log and bail silently
                error_log('[Mailer] SMTP not configured. Set SMTP_HOST, SMTP_USER, SMTP_PASS in .env');
                return false;
            }

            $mail->isSMTP();
            $mail->Host       = $host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $user;
            $mail->Password   = $pass;
            $mail->Port       = $port;
            $mail->SMTPSecure = $port === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->CharSet    = 'UTF-8';

            // ── From ───────────────────────────────────────────────────────
            $mail->setFrom($from, $fromName);
            $mail->addReplyTo($from, $fromName);

            // ── To ─────────────────────────────────────────────────────────
            if (is_array($to)) {
                $mail->addAddress($to['email'], $to['name'] ?? '');
            } else {
                $mail->addAddress($to);
            }

            // ── Content ────────────────────────────────────────────────────
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = $text ?? strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $html));

            $mail->send();
            return true;

        } catch (MailerException $e) {
            error_log('[Mailer] Send failed: ' . $mail->ErrorInfo);
            return false;
        } catch (\Throwable $e) {
            error_log('[Mailer] Unexpected error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Render an email template from views/emails/{template}.php
     * and inject variables.
     */
    public static function render(string $template, array $vars = []): string
    {
        $file = BASE_PATH . '/views/emails/' . ltrim($template, '/') . '.php';
        if (!file_exists($file)) {
            throw new \RuntimeException("Email template not found: {$file}");
        }
        extract($vars, EXTR_SKIP);
        ob_start();
        include $file;
        return ob_get_clean();
    }
}
