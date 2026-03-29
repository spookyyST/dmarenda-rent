<?php

declare(strict_types=1);

namespace Rent\Service;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Rent\Support\Logger;

class MailService
{
    public function __construct(private readonly array $config, private readonly Logger $logger, private readonly FileStorageService $storage)
    {
    }

    public function send(string $to, string $subject, string $html, array $attachments = []): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = (string) app_config($this->config, 'smtp.host');
            $mail->Port = (int) app_config($this->config, 'smtp.port');
            $mail->SMTPAuth = true;
            $mail->Username = (string) app_config($this->config, 'smtp.user');
            $mail->Password = (string) app_config($this->config, 'smtp.pass');

            $encryption = (string) app_config($this->config, 'smtp.encryption', 'tls');
            if ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->setFrom(
                (string) app_config($this->config, 'smtp.from_email', 'ids@drmhhh.com'),
                (string) app_config($this->config, 'smtp.from_name', 'ДМаренда')
            );
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $html;

            foreach ($attachments as $attachment) {
                if (!is_string($attachment) || $attachment === '') {
                    continue;
                }
                $absolute = $this->storage->absolutePath($attachment);
                if (is_file($absolute)) {
                    $mail->addAttachment($absolute);
                }
            }

            $mail->send();
            return true;
        } catch (Exception $e) {
            $this->logger->error('SMTP send failed', ['to' => $to, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
