<?php

declare(strict_types=1);

namespace Rent\Service;

use PDO;
use RuntimeException;
use Rent\Repository\ContractRepository;
use Rent\Repository\InvitationRepository;
use Rent\Repository\TenantRepository;
use Rent\Repository\UserRepository;

class TenantRegistrationService
{
    public function __construct(
        private readonly array $config,
        private readonly PDO $pdo,
        private readonly UserRepository $userRepository,
        private readonly TenantRepository $tenantRepository,
        private readonly InvitationRepository $invitationRepository,
        private readonly ContractRepository $contractRepository,
        private readonly FileStorageService $fileStorage,
        private readonly NotificationService $notificationService,
        private readonly PdfService $pdfService,
        private readonly ContentService $contentService
    ) {
    }

    public function registerByInvitation(array $invitation, array $input, array $files, string $ip): array
    {
        $timezone = (string) app_config($this->config, 'app.timezone', 'Europe/Moscow');
        $now = app_now($timezone)->format('Y-m-d H:i:s');

        $passportMain = $this->fileStorage->saveUploadedFile($files['passport_scan_main'] ?? [], 'passports');
        $passportAddress = $this->fileStorage->saveUploadedFile($files['passport_scan_address'] ?? [], 'passports');
        $contractPdfPath = null;

        $existingUser = $this->userRepository->findByEmail($input['email']);
        if ($existingUser !== null && ($existingUser['role'] ?? '') !== 'tenant') {
            throw new RuntimeException('Этот email уже используется в другой роли пользователя.');
        }

        $this->pdo->beginTransaction();

        try {
            if ($existingUser !== null) {
                $tenantUserId = (int) $existingUser['id'];
            } else {
                $tenantUserId = $this->userRepository->create(
                    'tenant',
                    $input['full_name'],
                    $input['email'],
                    $input['phone'],
                    null,
                    $now
                );
            }

            $this->tenantRepository->upsert($tenantUserId, [
                'passport_series' => $input['passport_series'],
                'passport_number' => $input['passport_number'],
                'passport_issued_by' => $input['passport_issued_by'],
                'passport_date' => $input['passport_date'],
                'registration_address' => $input['registration_address'],
                'passport_scan_main' => $passportMain,
                'passport_scan_address' => $passportAddress,
                'consent_pd_at' => $now,
                'consent_policy_at' => $now,
                'consent_contract_at' => $now,
                'consent_ip' => $ip,
            ]);

            $this->invitationRepository->updateStatus((int) $invitation['id'], 'registered');

            $contract = $this->contractRepository->findByInvitationId((int) $invitation['id']);
            if ($contract === null) {
                $contractId = $this->contractRepository->create(
                    (int) $invitation['id'],
                    (int) $invitation['landlord_id'],
                    $tenantUserId,
                    null,
                    $now
                );
                $contract = $this->contractRepository->findById($contractId);
            }

            if ($contract !== null && empty($contract['pdf_path'])) {
                $payload = $this->buildContractPdfPayload($invitation, $input);
                $contractPdfPath = $this->pdfService->generateContractPdf($payload);
                $this->contractRepository->updatePdfPath((int) $contract['id'], $contractPdfPath);
            } elseif ($contract !== null) {
                $contractPdfPath = (string) $contract['pdf_path'];
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw new RuntimeException('Не удалось завершить регистрацию: ' . $e->getMessage());
        }

        $tenantUser = $this->userRepository->findById($tenantUserId);
        if ($tenantUser !== null) {
            $this->notificationService->sendRegistrationEmail($tenantUser, $invitation, $contractPdfPath);
        }

        return $this->userRepository->findById($tenantUserId) ?? [];
    }

    private function buildContractPdfPayload(array $invitation, array $input): array
    {
        $timezone = (string) app_config($this->config, 'app.timezone', 'Europe/Moscow');
        $contractDate = app_now($timezone);
        $landlord = app_config($this->config, 'landlord.details', []);

        $variables = [
            'city' => (string) app_config($this->config, 'app.city', 'Москва'),
            'contract_date_day' => $contractDate->format('d'),
            'contract_date_month' => $this->monthNameRu((int) $contractDate->format('n')),
            'contract_date_year' => $contractDate->format('Y'),
            'tenant_full_name' => (string) $input['full_name'],
            'tenant_passport_series' => (string) $input['passport_series'],
            'tenant_passport_number' => (string) $input['passport_number'],
            'tenant_passport_issued_by' => (string) $input['passport_issued_by'],
            'tenant_passport_date' => (string) $input['passport_date'],
            'tenant_registration_address' => (string) $input['registration_address'],
            'tenant_phone' => (string) $input['phone'],
            'tenant_email' => (string) $input['email'],
            'property_address' => (string) $invitation['property_address'],
            'rent_amount' => number_format((float) $invitation['rent_amount'], 2, '.', ' '),
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
            'invitation' => $invitation,
            'service_name' => app_config($this->config, 'app.name', 'ДМаренда'),
            'contract_html' => $this->contentService->renderContractHtml($variables),
        ];
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
