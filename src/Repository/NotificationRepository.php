<?php

declare(strict_types=1);

namespace Rent\Repository;

use PDO;

class NotificationRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(array $payload): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO notifications(
            contract_id, tenant_id, type, channel, recipient, payload_json, status, scheduled_for, sent_at, created_at)
            VALUES(:contract_id, :tenant_id, :type, :channel, :recipient, :payload_json, :status, :scheduled_for, :sent_at, :created_at)');
        $stmt->execute($payload);

        return (int) $this->pdo->lastInsertId();
    }

    public function existsSentReminder(int $contractId, string $scheduledFor): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM notifications
            WHERE contract_id = :contract_id
              AND type = :type
              AND scheduled_for = :scheduled_for
              AND status = :status
            LIMIT 1');

        $stmt->execute([
            'contract_id' => $contractId,
            'type' => 'reminder_5days',
            'scheduled_for' => $scheduledFor,
            'status' => 'sent',
        ]);

        return (bool) $stmt->fetchColumn();
    }
}
