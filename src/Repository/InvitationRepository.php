<?php

declare(strict_types=1);

namespace Rent\Repository;

use PDO;

class InvitationRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(array $payload): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO invitations(landlord_id, email, phone, property_address, rent_amount, start_date, token, status, created_at)
            VALUES(:landlord_id, :email, :phone, :property_address, :rent_amount, :start_date, :token, :status, :created_at)');
        $stmt->execute($payload);
        return (int) $this->pdo->lastInsertId();
    }

    public function findByToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM invitations WHERE token = :token LIMIT 1');
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM invitations WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function listAll(?string $status = null): array
    {
        if ($status !== null && in_array($status, ['new', 'registered', 'paid'], true)) {
            $stmt = $this->pdo->prepare('SELECT * FROM invitations WHERE status = :status ORDER BY id DESC');
            $stmt->execute(['status' => $status]);
            return $stmt->fetchAll();
        }

        return $this->pdo->query('SELECT * FROM invitations ORDER BY id DESC')->fetchAll();
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE invitations SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }
}
