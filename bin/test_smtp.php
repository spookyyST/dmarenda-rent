<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config.php';

$mail = new PHPMailer\PHPMailer\PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = (string) ($config['smtp']['host'] ?? '');
    $mail->SMTPAuth   = true;
    $mail->Username   = (string) ($config['smtp']['user'] ?? '');
    $mail->Password   = (string) ($config['smtp']['pass'] ?? '');
    $mail->SMTPSecure = (string) ($config['smtp']['encryption'] ?? 'ssl');
    $mail->Port       = (int) ($config['smtp']['port'] ?? 465);
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom(
        (string) ($config['smtp']['from_email'] ?? ''),
        (string) ($config['smtp']['from_name'] ?? 'Test')
    );
    $mail->addAddress((string) ($config['smtp']['from_email'] ?? ''));
    $mail->Subject = 'Тест SMTP — ДМаренда';
    $mail->isHTML(true);
    $mail->Body    = '<p>✅ SMTP работает! Письмо успешно отправлено.</p>';
    $mail->AltBody = 'SMTP работает! Письмо успешно отправлено.';

    $mail->send();
    echo "✅ OK: Письмо отправлено на " . $mail->Username . "\n";
} catch (Exception $e) {
    echo "❌ ERROR: " . $mail->ErrorInfo . "\n";
}
