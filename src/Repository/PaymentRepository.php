<?php

declare(strict_types=1);

namespace Rent\Repository;

use PDO;

class PaymentRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(array $payload): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO payments(contract_id, amount, status, payment_id, yookassa_status, receipt_pdf_path, next_payment_date, paid_at, created_at)
            VALUES(:contract_id, :amount, :status, :payment_id, :yookassa_status, :receipt_pdf_path, :next_payment_date, :paid_at, :created_at)');
        $stmt->execute($payload);

        return (int) $this->pdo->lastInsertId();
    }

    public function findByPaymentId(string $paymentId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM payments WHERE payment_id = :payment_id LIMIT 1');
        $stmt->execute(['payment_id' => $paymentId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM payments WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateSucceeded(int $id, string $receiptPdfPath, string $nextPaymentDate, string $paidAt): void
    {
        $stmt = $this->pdo->prepare('UPDATE payments
            SET status = :status,
                yookassa_status = :yookassa_status,
                receipt_pdf_path = :receipt_pdf_path,
                next_payment_date = :next_payment_date,
                paid_at = :paid_at
            WHERE id = :id');

        $stmt->execute([
            'status' => 'succeeded',
            'yookassa_status' => 'succeeded',
            'receipt_pdf_path' => $receiptPdfPath,
            'next_payment_date' => $nextPaymentDate,
            'paid_at' => $paidAt,
            'id' => $id,
        ]);
    }

    public function updateCanceled(int $id, string $yookassaStatus): void
    {
        $stmt = $this->pdo->prepare('UPDATE payments SET status = :status, yookassa_status = :yookassa_status WHERE id = :id');
        $stmt->execute([
            'status' => 'canceled',
            'yookassa_status' => $yookassaStatus,
            'id' => $id,
        ]);
    }

    public function listAll(): array
    {
        return $this->pdo->query('SELECT p.*, c.invitation_id, i.token, i.property_address, i.rent_amount,
                u.full_name AS tenant_name, u.email AS tenant_email
            FROM payments p
            JOIN contracts c ON c.id = p.contract_id
            JOIN invitations i ON i.id = c.invitation_id
            JOIN users u ON u.id = c.tenant_id
            ORDER BY p.id DESC')->fetchAll();
    }

    public function latestSucceededByContract(int $contractId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM payments
            WHERE contract_id = :contract_id AND status = :status
            ORDER BY id DESC LIMIT 1');
        $stmt->execute([
            'contract_id' => $contractId,
            'status' => 'succeeded',
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function latestByContract(int $contractId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM payments WHERE contract_id = :contract_id ORDER BY id DESC LIMIT 1');
        $stmt->execute(['contract_id' => $contractId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function listByContract(int $contractId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM payments WHERE contract_id = :contract_id ORDER BY id DESC');
        $stmt->execute(['contract_id' => $contractId]);
        return $stmt->fetchAll();
    }

    public function findDueReminders(string $targetDate): array
    {
        $stmt = $this->pdo->prepare('SELECT p.*, c.id AS contract_id, c.tenant_id, c.invitation_id,
                i.token, i.property_address,
                u.full_name AS tenant_name, u.email AS tenant_email
            FROM payments p
            JOIN contracts c ON c.id = p.contract_id
            JOIN invitations i ON i.id = c.invitation_id
            JOIN users u ON u.id = c.tenant_id
            WHERE p.status = :status_main
              AND p.next_payment_date = :target_date
              AND p.id = (
                SELECT p2.id FROM payments p2
                WHERE p2.contract_id = p.contract_id AND p2.status = :status_sub
                ORDER BY p2.id DESC LIMIT 1
              )');
        $stmt->execute([
            'status_main' => 'succeeded',
            'status_sub' => 'succeeded',
            'target_date' => $targetDate,
        ]);

        return $stmt->fetchAll();
    }
}
