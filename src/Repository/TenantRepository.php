<?php

declare(strict_types=1);

namespace Rent\Repository;

use PDO;

class TenantRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function upsert(int $userId, array $payload): void
    {
        $existsStmt = $this->pdo->prepare('SELECT user_id FROM tenants WHERE user_id = :user_id');
        $existsStmt->execute(['user_id' => $userId]);
        $exists = (bool) $existsStmt->fetchColumn();

        $payload['user_id'] = $userId;

        if ($exists) {
            $stmt = $this->pdo->prepare('UPDATE tenants SET
                passport_series = :passport_series,
                passport_number = :passport_number,
                passport_issued_by = :passport_issued_by,
                passport_date = :passport_date,
                registration_address = :registration_address,
                passport_scan_main = :passport_scan_main,
                passport_scan_address = :passport_scan_address,
                consent_pd_at = :consent_pd_at,
                consent_policy_at = :consent_policy_at,
                consent_contract_at = :consent_contract_at,
                consent_ip = :consent_ip
                WHERE user_id = :user_id');
            $stmt->execute($payload);
            return;
        }

        $stmt = $this->pdo->prepare('INSERT INTO tenants(
            user_id, passport_series, passport_number, passport_issued_by, passport_date, registration_address,
            passport_scan_main, passport_scan_address, consent_pd_at, consent_policy_at, consent_contract_at, consent_ip)
            VALUES(:user_id, :passport_series, :passport_number, :passport_issued_by, :passport_date, :registration_address,
            :passport_scan_main, :passport_scan_address, :consent_pd_at, :consent_policy_at, :consent_contract_at, :consent_ip)');
        $stmt->execute($payload);
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tenants WHERE user_id = :user_id LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
