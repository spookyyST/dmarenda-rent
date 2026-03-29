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
        private readonly NotificationService $notificationService
    ) {
    }

    public function registerByInvitation(array $invitation, array $input, array $files, string $ip): array
    {
        $timezone = (string) app_config($this->config, 'app.timezone', 'Europe/Moscow');
        $now = app_now($timezone)->format('Y-m-d H:i:s');

        $passportMain = $this->fileStorage->saveUploadedFile($files['passport_scan_main'] ?? [], 'passports');
        $passportAddress = $this->fileStorage->saveUploadedFile($files['passport_scan_address'] ?? [], 'passports');

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
                $this->contractRepository->create(
                    (int) $invitation['id'],
                    (int) $invitation['landlord_id'],
                    $tenantUserId,
                    null,
                    $now
                );
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw new RuntimeException('Не удалось завершить регистрацию: ' . $e->getMessage());
        }

        $tenantUser = $this->userRepository->findById($tenantUserId);
        if ($tenantUser !== null) {
            $this->notificationService->sendRegistrationEmail($tenantUser, $invitation);
        }

        return $this->userRepository->findById($tenantUserId) ?? [];
    }
}
