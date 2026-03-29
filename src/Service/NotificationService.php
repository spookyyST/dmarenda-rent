<?php

declare(strict_types=1);

namespace Rent\Service;

use Rent\Repository\NotificationRepository;
use Rent\Support\View;

class NotificationService
{
    public function __construct(
        private readonly array $config,
        private readonly MailService $mailService,
        private readonly NotificationRepository $notificationRepository,
        private readonly View $view
    ) {
    }

    public function sendRegistrationEmail(array $tenant, array $invitation): void
    {
        $subject = 'Регистрация в ДМаренда завершена';
        $cabinetLink = rtrim((string) app_config($this->config, 'app.base_url'), '/') . '/i/' . $invitation['token'] . '/cabinet';
        $html = $this->view->render('emails/registration.php', [
            'tenant' => $tenant,
            'invitation' => $invitation,
            'cabinet_link' => $cabinetLink,
            'app' => $this->config['app'],
        ]);

        $ok = $this->mailService->send((string) $tenant['email'], $subject, $html);

        $this->notificationRepository->create([
            'contract_id' => null,
            'tenant_id' => (int) $tenant['id'],
            'type' => 'registration',
            'channel' => 'email',
            'recipient' => (string) $tenant['email'],
            'payload_json' => json_encode(['invitation_id' => (int) $invitation['id']], JSON_UNESCAPED_UNICODE),
            'status' => $ok ? 'sent' : 'failed',
            'scheduled_for' => null,
            'sent_at' => $ok ? app_now((string) app_config($this->config, 'app.timezone'))->format('Y-m-d H:i:s') : null,
            'created_at' => app_now((string) app_config($this->config, 'app.timezone'))->format('Y-m-d H:i:s'),
        ]);
    }

    public function sendPaymentSuccessEmail(array $tenant, array $invitation, array $payment, string $contractPdf, string $receiptPdf): void
    {
        $subject = 'Оплата аренды подтверждена';
        $html = $this->view->render('emails/payment_success.php', [
            'tenant' => $tenant,
            'invitation' => $invitation,
            'payment' => $payment,
            'app' => $this->config['app'],
        ]);

        $ok = $this->mailService->send((string) $tenant['email'], $subject, $html, [$contractPdf, $receiptPdf]);

        $this->notificationRepository->create([
            'contract_id' => (int) $payment['contract_id'],
            'tenant_id' => (int) $tenant['id'],
            'type' => 'payment_success',
            'channel' => 'email',
            'recipient' => (string) $tenant['email'],
            'payload_json' => json_encode(['payment_id' => $payment['payment_id']], JSON_UNESCAPED_UNICODE),
            'status' => $ok ? 'sent' : 'failed',
            'scheduled_for' => null,
            'sent_at' => $ok ? app_now((string) app_config($this->config, 'app.timezone'))->format('Y-m-d H:i:s') : null,
            'created_at' => app_now((string) app_config($this->config, 'app.timezone'))->format('Y-m-d H:i:s'),
        ]);
    }

    public function sendReminderEmail(array $payload, string $scheduledFor): bool
    {
        $subject = 'Напоминание об оплате аренды';
        $payLink = rtrim((string) app_config($this->config, 'app.base_url'), '/') . '/i/' . $payload['token'] . '/cabinet';

        $html = $this->view->render('emails/reminder.php', [
            'tenant' => [
                'full_name' => $payload['tenant_name'],
                'email' => $payload['tenant_email'],
            ],
            'invitation' => [
                'property_address' => $payload['property_address'],
            ],
            'pay_link' => $payLink,
            'next_payment_date' => $payload['next_payment_date'],
            'app' => $this->config['app'],
        ]);

        $ok = $this->mailService->send((string) $payload['tenant_email'], $subject, $html);

        $this->notificationRepository->create([
            'contract_id' => (int) $payload['contract_id'],
            'tenant_id' => (int) $payload['tenant_id'],
            'type' => 'reminder_5days',
            'channel' => 'email',
            'recipient' => (string) $payload['tenant_email'],
            'payload_json' => json_encode(['token' => $payload['token']], JSON_UNESCAPED_UNICODE),
            'status' => $ok ? 'sent' : 'failed',
            'scheduled_for' => $scheduledFor,
            'sent_at' => $ok ? app_now((string) app_config($this->config, 'app.timezone'))->format('Y-m-d H:i:s') : null,
            'created_at' => app_now((string) app_config($this->config, 'app.timezone'))->format('Y-m-d H:i:s'),
        ]);

        return $ok;
    }
}
