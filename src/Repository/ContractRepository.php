<?php

declare(strict_types=1);

namespace Rent\Repository;

use PDO;

class ContractRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(int $invitationId, int $landlordId, int $tenantId, ?string $pdfPath, string $createdAt): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO contracts(invitation_id, landlord_id, tenant_id, pdf_path, created_at)
            VALUES(:invitation_id, :landlord_id, :tenant_id, :pdf_path, :created_at)');
        $stmt->execute([
            'invitation_id' => $invitationId,
            'landlord_id' => $landlordId,
            'tenant_id' => $tenantId,
            'pdf_path' => $pdfPath,
            'created_at' => $createdAt,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findByInvitationId(int $invitationId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM contracts WHERE invitation_id = :invitation_id LIMIT 1');
        $stmt->execute(['invitation_id' => $invitationId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM contracts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function listAll(?string $status = null): array
    {
        if ($status !== null && in_array($status, ['new', 'registered', 'paid'], true)) {
            $stmt = $this->pdo->prepare('SELECT c.*, i.token, i.email AS invitation_email, i.property_address, i.rent_amount, i.start_date, i.status AS invitation_status,
                    u.full_name AS tenant_name, u.email AS tenant_email
                FROM contracts c
                JOIN invitations i ON i.id = c.invitation_id
                JOIN users u ON u.id = c.tenant_id
                WHERE i.status = :status
                ORDER BY c.id DESC');
            $stmt->execute(['status' => $status]);
            return $stmt->fetchAll();
        }

        return $this->pdo->query('SELECT c.*, i.token, i.email AS invitation_email, i.property_address, i.rent_amount, i.start_date, i.status AS invitation_status,
                u.full_name AS tenant_name, u.email AS tenant_email
            FROM contracts c
            JOIN invitations i ON i.id = c.invitation_id
            JOIN users u ON u.id = c.tenant_id
            ORDER BY c.id DESC')->fetchAll();
    }

    public function updatePdfPath(int $id, string $pdfPath): void
    {
        $stmt = $this->pdo->prepare('UPDATE contracts SET pdf_path = :pdf_path WHERE id = :id');
        $stmt->execute(['pdf_path' => $pdfPath, 'id' => $id]);
    }
}
