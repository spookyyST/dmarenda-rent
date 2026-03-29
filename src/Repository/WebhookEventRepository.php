<?php

declare(strict_types=1);

namespace Rent\Repository;

use PDO;

class WebhookEventRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function wasProcessed(string $eventId): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM webhook_events WHERE event_id = :event_id LIMIT 1');
        $stmt->execute(['event_id' => $eventId]);
        return (bool) $stmt->fetchColumn();
    }

    public function markProcessed(string $eventId, string $eventType, ?string $paymentId, string $receivedAt): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO webhook_events(event_id, event_type, payment_id, received_at)
            VALUES(:event_id, :event_type, :payment_id, :received_at)');
        $stmt->execute([
            'event_id' => $eventId,
            'event_type' => $eventType,
            'payment_id' => $paymentId,
            'received_at' => $receivedAt,
        ]);
    }
}
