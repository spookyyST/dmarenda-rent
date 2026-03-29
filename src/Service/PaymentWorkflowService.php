<?php

declare(strict_types=1);

namespace Rent\Service;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use RuntimeException;
use Rent\Repository\ContractRepository;
use Rent\Repository\InvitationRepository;
use Rent\Repository\PaymentRepository;
use Rent\Repository\TenantRepository;
use Rent\Repository\UserRepository;
use Rent\Repository\WebhookEventRepository;

class PaymentWorkflowService
{
    public function __construct(
        private readonly array $config,
        private readonly PDO $pdo,
        private readonly YookassaService $yookassa,
        private readonly PaymentRepository $paymentRepository,
        private readonly ContractRepository $contractRepository,
        private readonly InvitationRepository $invitationRepository,
        private readonly UserRepository $userRepository,
        private readonly TenantRepository $tenantRepository,
        private readonly WebhookEventRepository $webhookEventRepository,
        private readonly PdfService $pdfService,
        private readonly NotificationService $notificationService,
        private readonly ContentService $contentService
    ) {
    }

    public function createPaymentForInvitation(array $invitation): array
    {
        $contract = $this->contractRepository->findByInvitationId((int) $invitation['id']);
        if ($contract === null) {
            throw new RuntimeException('Договор не найден для приглашения.');
        }

        $tenant = $this->userRepository->findById((int) $contract['tenant_id']);
        if ($tenant === null) {
            throw new RuntimeException('Арендатор не найден.');
        }

        $returnUrl = rtrim((string) app_config($this->config, 'app.base_url'), '/') . '/i/' . $invitation['token'] . '/pay/return';

        $description = sprintf(
            'Аренда %s (%s)',
            $invitation['property_address'],
            $tenant['full_name']
        );

        if ((bool) app_config($this->config, 'yookassa.test_mode', false)) {
            $paymentId = 'fake_' . bin2hex(random_bytes(8));

            $this->paymentRepository->create([
                'contract_id' => (int) $contract['id'],
                'amount' => (float) $invitation['rent_amount'],
                'status' => 'pending',
                'payment_id' => $paymentId,
                'yookassa_status' => 'pending',
                'receipt_pdf_path' => null,
                'next_payment_date' => null,
                'paid_at' => null,
                'created_at' => app_now((string) app_config($this->config, 'app.timezone'))->format('Y-m-d H:i:s'),
            ]);

            return [
                'payment_id' => $paymentId,
                'confirmation_url' => rtrim((string) app_config($this->config, 'app.base_url'), '/') . '/i/' . $invitation['token'] . '/pay/fake-confirm/' . $paymentId,
            ];
        }

        $payment = $this->yookassa->createPayment(
            (float) $invitation['rent_amount'],
            $description,
            $returnUrl,
            [
                'contract_id' => (string) $contract['id'],
                'invitation_id' => (string) $invitation['id'],
                'tenant_id' => (string) $tenant['id'],
                'purpose' => 'rent_payment',
            ]
        );

        $paymentId = (string) ($payment['id'] ?? '');
        if ($paymentId === '') {
            throw new RuntimeException('ЮKassa не вернула payment_id.');
        }

        $this->paymentRepository->create([
            'contract_id' => (int) $contract['id'],
            'amount' => (float) $invitation['rent_amount'],
            'status' => 'pending',
            'payment_id' => $paymentId,
            'yookassa_status' => (string) ($payment['status'] ?? 'pending'),
            'receipt_pdf_path' => null,
            'next_payment_date' => null,
            'paid_at' => null,
            'created_at' => app_now((string) app_config($this->config, 'app.timezone'))->format('Y-m-d H:i:s'),
        ]);

        $confirmationUrl = (string) ($payment['confirmation']['confirmation_url'] ?? '');
        if ($confirmationUrl === '') {
            throw new RuntimeException('ЮKassa не вернула URL подтверждения оплаты.');
        }

        return [
            'payment_id' => $paymentId,
            'confirmation_url' => $confirmationUrl,
        ];
    }

    public function processFakeConfirmation(string $token, string $paymentId): void
    {
        if (!(bool) app_config($this->config, 'yookassa.test_mode', false)) {
            throw new RuntimeException('Тестовый режим оплаты отключен.');
        }

        $invitation = $this->invitationRepository->findByToken($token);
        if ($invitation === null) {
            throw new RuntimeException('Приглашение не найдено.');
        }

        $contract = $this->contractRepository->findByInvitationId((int) $invitation['id']);
        if ($contract === null) {
            throw new RuntimeException('Договор не найден.');
        }

        $existingPayment = $this->paymentRepository->findByPaymentId($paymentId);
        if ($existingPayment === null || (int) $existingPayment['contract_id'] !== (int) $contract['id']) {
            throw new RuntimeException('Платеж не найден.');
        }

        if ((string) $existingPayment['status'] === 'succeeded') {
            return;
        }

        $this->pdo->beginTransaction();

        try {
            $tenant = $this->userRepository->findById((int) $contract['tenant_id']);
            $tenantProfile = $this->tenantRepository->findByUserId((int) $contract['tenant_id']);

            if ($tenant === null || $tenantProfile === null) {
                throw new RuntimeException('Данные арендатора не найдены.');
            }

            $contractPdfPath = (string) ($contract['pdf_path'] ?? '');
            if ($contractPdfPath === '') {
                $contractPdfPath = $this->pdfService->generateContractPdf($this->buildContractPdfPayload($invitation, $tenant, $tenantProfile));
                $this->contractRepository->updatePdfPath((int) $contract['id'], $contractPdfPath);
            }

            $paidAt = app_now((string) app_config($this->config, 'app.timezone'))->format('Y-m-d H:i:s');
            $receiptPdfPath = $this->pdfService->generateReceiptPdf([
                'tenant' => $tenant,
                'invitation' => $invitation,
                'payment_id' => $existingPayment['payment_id'],
                'amount' => $existingPayment['amount'],
                'paid_at' => $paidAt,
                'city' => app_config($this->config, 'app.city', 'Москва'),
                'service_name' => app_config($this->config, 'app.name', 'ДМаренда'),
            ]);

            $nextPaymentDate = $this->calculateNextPaymentDate((int) $contract['id'], (string) $invitation['start_date']);
            $this->paymentRepository->updateSucceeded((int) $existingPayment['id'], $receiptPdfPath, $nextPaymentDate, $paidAt);
            $this->invitationRepository->updateStatus((int) $invitation['id'], 'paid');

            $this->pdo->commit();

            $freshPayment = $this->paymentRepository->findByPaymentId($paymentId);
            if ($freshPayment !== null) {
                $this->notificationService->sendPaymentSuccessEmail($tenant, $invitation, $freshPayment, $contractPdfPath, $receiptPdfPath);
            }
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }


    public function handleWebhook(array $event, string $requestIp): void
    {
        $eventId = (string) ($event['id'] ?? '');
        if ($eventId === '') {
            $eventId = hash('sha256', (string) json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        $eventType = (string) ($event['event'] ?? '');

        if ($eventId === '' || $eventType === '') {
            throw new RuntimeException('Невалидный webhook payload.');
        }

        if (!$this->isAllowedIp($requestIp)) {
            throw new RuntimeException('Webhook IP is not allowed.');
        }

        if ($this->webhookEventRepository->wasProcessed($eventId)) {
            return;
        }

        $object = $event['object'] ?? null;
        if (!is_array($object)) {
            throw new RuntimeException('Webhook object is missing.');
        }

        $paymentId = (string) ($object['id'] ?? '');

        if ($paymentId === '') {
            $this->markWebhookProcessed($eventId, $eventType, null);
            return;
        }

        $existingPayment = $this->paymentRepository->findByPaymentId($paymentId);
        if ($existingPayment === null) {
            $this->markWebhookProcessed($eventId, $eventType, $paymentId);
            return;
        }

        $verifiedPayment = $this->yookassa->getPayment($paymentId);
        $status = (string) ($verifiedPayment['status'] ?? '');

        if ($status === 'canceled') {
            $this->paymentRepository->updateCanceled((int) $existingPayment['id'], 'canceled');
            $this->markWebhookProcessed($eventId, $eventType, $paymentId);
            return;
        }

        if ($status !== 'succeeded' || $eventType !== 'payment.succeeded') {
            $this->markWebhookProcessed($eventId, $eventType, $paymentId);
            return;
        }

        if ((string) $existingPayment['status'] === 'succeeded') {
            $this->markWebhookProcessed($eventId, $eventType, $paymentId);
            return;
        }

        $paidAmount = (float) ($verifiedPayment['amount']['value'] ?? 0);
        if (abs($paidAmount - (float) $existingPayment['amount']) > 0.009) {
            throw new RuntimeException('Webhook amount mismatch.');
        }

        $metaContractId = (int) ($verifiedPayment['metadata']['contract_id'] ?? 0);
        if ($metaContractId > 0 && $metaContractId !== (int) $existingPayment['contract_id']) {
            throw new RuntimeException('Webhook contract mismatch.');
        }

        $this->pdo->beginTransaction();

        try {
            $contract = $this->contractRepository->findById((int) $existingPayment['contract_id']);
            if ($contract === null) {
                throw new RuntimeException('Contract not found.');
            }

            $invitation = $this->invitationRepository->findById((int) $contract['invitation_id']);
            $tenant = $this->userRepository->findById((int) $contract['tenant_id']);
            $tenantProfile = $this->tenantRepository->findByUserId((int) $contract['tenant_id']);

            if ($invitation === null || $tenant === null || $tenantProfile === null) {
                throw new RuntimeException('Contract bindings are broken.');
            }

            $metaInvitationId = (int) ($verifiedPayment['metadata']['invitation_id'] ?? 0);
            if ($metaInvitationId > 0 && $metaInvitationId !== (int) $invitation['id']) {
                throw new RuntimeException('Webhook invitation mismatch.');
            }

            $metaTenantId = (int) ($verifiedPayment['metadata']['tenant_id'] ?? 0);
            if ($metaTenantId > 0 && $metaTenantId !== (int) $tenant['id']) {
                throw new RuntimeException('Webhook tenant mismatch.');
            }

            $purpose = (string) ($verifiedPayment['metadata']['purpose'] ?? '');
            if ($purpose !== '' && $purpose !== 'rent_payment') {
                throw new RuntimeException('Webhook purpose mismatch.');
            }

            $contractPdfPath = (string) ($contract['pdf_path'] ?? '');
            if ($contractPdfPath === '') {
                $contractPdfPath = $this->pdfService->generateContractPdf($this->buildContractPdfPayload($invitation, $tenant, $tenantProfile));
                $this->contractRepository->updatePdfPath((int) $contract['id'], $contractPdfPath);
            }

            $receiptPdfPath = $this->pdfService->generateReceiptPdf([
                'tenant' => $tenant,
                'invitation' => $invitation,
                'payment_id' => $existingPayment['payment_id'],
                'amount' => $existingPayment['amount'],
                'paid_at' => app_now((string) app_config($this->config, 'app.timezone'))->format('Y-m-d H:i:s'),
                'city' => app_config($this->config, 'app.city', 'Москва'),
                'service_name' => app_config($this->config, 'app.name', 'ДМаренда'),
            ]);

            $nextPaymentDate = $this->calculateNextPaymentDate((int) $contract['id'], (string) $invitation['start_date']);

            $paidAt = app_now((string) app_config($this->config, 'app.timezone'))->format('Y-m-d H:i:s');
            $this->paymentRepository->updateSucceeded((int) $existingPayment['id'], $receiptPdfPath, $nextPaymentDate, $paidAt);
            $this->invitationRepository->updateStatus((int) $invitation['id'], 'paid');

            $this->pdo->commit();
            $this->markWebhookProcessed($eventId, $eventType, $paymentId);

            $freshPayment = $this->paymentRepository->findByPaymentId($paymentId);
            if ($freshPayment !== null) {
                $this->notificationService->sendPaymentSuccessEmail($tenant, $invitation, $freshPayment, $contractPdfPath, $receiptPdfPath);
            }
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function calculateNextPaymentDate(int $contractId, string $startDate): string
    {
        $timezone = new DateTimeZone((string) app_config($this->config, 'app.timezone', 'Europe/Moscow'));
        $latest = $this->paymentRepository->latestSucceededByContract($contractId);

        if ($latest !== null && !empty($latest['next_payment_date'])) {
            $base = new DateTimeImmutable((string) $latest['next_payment_date'], $timezone);
            return $base->modify('+1 month')->format('Y-m-d');
        }

        $start = new DateTimeImmutable($startDate, $timezone);
        return $start->modify('+1 month')->format('Y-m-d');
    }

    private function buildContractPdfPayload(array $invitation, array $tenant, array $tenantProfile): array
    {
        $timezone = (string) app_config($this->config, 'app.timezone', 'Europe/Moscow');
        $contractDate = app_now($timezone);
        $landlord = app_config($this->config, 'landlord.details', []);
        $rentAmountFormatted = number_format((float) $invitation['rent_amount'], 2, '.', ' ');
        $variables = [
            'city' => (string) app_config($this->config, 'app.city', 'Москва'),
            'contract_date_day' => $contractDate->format('d'),
            'contract_date_month' => $this->monthNameRu((int) $contractDate->format('n')),
            'contract_date_year' => $contractDate->format('Y'),
            'tenant_full_name' => (string) $tenant['full_name'],
            'tenant_passport_series' => (string) $tenantProfile['passport_series'],
            'tenant_passport_number' => (string) $tenantProfile['passport_number'],
            'tenant_passport_issued_by' => (string) $tenantProfile['passport_issued_by'],
            'tenant_passport_date' => (string) $tenantProfile['passport_date'],
            'tenant_registration_address' => (string) $tenantProfile['registration_address'],
            'tenant_phone' => (string) $tenant['phone'],
            'tenant_email' => (string) $tenant['email'],
            'property_address' => (string) $invitation['property_address'],
            'rent_amount' => $rentAmountFormatted,
            'landlord_full_name' => (string) ($landlord['full_name'] ?? ''),
            'landlord_type' => (string) ($landlord['type'] ?? ''),
            'landlord_inn' => (string) ($landlord['inn'] ?? ''),
            'landlord_passport_series' => (string) ($landlord['passport_series'] ?? ''),
            'landlord_passport_number' => (string) ($landlord['passport_number'] ?? ''),
            'landlord_passport_issued_by' => (string) ($landlord['passport_issued_by'] ?? ''),
            'landlord_passport_date' => (string) ($landlord['passport_date'] ?? ''),
            'landlord_registration_address' => (string) ($landlord['registration_address'] ?? ''),
            'landlord_phone' => (string) ($landlord['phone'] ?? ''),
            'landlord_email' => (string) ($landlord['email'] ?? ''),
        ];

        return [
            'city' => $variables['city'],
            'contract_date' => $contractDate->format('Y-m-d'),
            'contract_date_day' => $variables['contract_date_day'],
            'contract_date_month' => $variables['contract_date_month'],
            'contract_date_year' => $variables['contract_date_year'],
            'tenant_full_name' => $variables['tenant_full_name'],
            'tenant_passport_series' => $variables['tenant_passport_series'],
            'tenant_passport_number' => $variables['tenant_passport_number'],
            'tenant_passport_issued_by' => $variables['tenant_passport_issued_by'],
            'tenant_passport_date' => $variables['tenant_passport_date'],
            'tenant_registration_address' => $variables['tenant_registration_address'],
            'tenant_phone' => $variables['tenant_phone'],
            'tenant_email' => $variables['tenant_email'],
            'property_address' => $variables['property_address'],
            'rent_amount' => $variables['rent_amount'],
            'landlord' => $landlord,
            'tenant' => $tenant,
            'tenant_profile' => $tenantProfile,
            'invitation' => $invitation,
            'service_name' => app_config($this->config, 'app.name', 'ДМаренда'),
            'contract_html' => $this->contentService->renderContractHtml($variables),
        ];
    }

    private function isAllowedIp(string $ip): bool
    {
        $allowlist = app_config($this->config, 'security.webhook_ip_allowlist', []);

        foreach ($allowlist as $allowed) {
            if ($this->ipMatches($ip, (string) $allowed)) {
                return true;
            }
        }

        return false;
    }

    private function ipMatches(string $ip, string $allowed): bool
    {
        if (!str_contains($allowed, '/')) {
            return $ip === $allowed;
        }

        [$subnet, $bits] = explode('/', $allowed, 2);
        $ipBinary = inet_pton($ip);
        $subnetBinary = inet_pton($subnet);

        if ($ipBinary === false || $subnetBinary === false || strlen($ipBinary) !== strlen($subnetBinary)) {
            return false;
        }

        $bits = (int) $bits;
        $bytes = intdiv($bits, 8);
        $remainder = $bits % 8;

        if ($bytes > 0 && substr($ipBinary, 0, $bytes) !== substr($subnetBinary, 0, $bytes)) {
            return false;
        }

        if ($remainder === 0) {
            return true;
        }

        $mask = chr((~(0xff >> $remainder)) & 0xff);

        return ($ipBinary[$bytes] & $mask) === ($subnetBinary[$bytes] & $mask);
    }

    private function markWebhookProcessed(string $eventId, string $eventType, ?string $paymentId): void
    {
        if ($this->webhookEventRepository->wasProcessed($eventId)) {
            return;
        }

        $this->webhookEventRepository->markProcessed(
            $eventId,
            $eventType,
            $paymentId,
            app_now((string) app_config($this->config, 'app.timezone'))->format('Y-m-d H:i:s')
        );
    }

    private function monthNameRu(int $month): string
    {
        $months = [
            1 => 'января',
            2 => 'февраля',
            3 => 'марта',
            4 => 'апреля',
            5 => 'мая',
            6 => 'июня',
            7 => 'июля',
            8 => 'августа',
            9 => 'сентября',
            10 => 'октября',
            11 => 'ноября',
            12 => 'декабря',
        ];

        return $months[$month] ?? '';
    }

}
