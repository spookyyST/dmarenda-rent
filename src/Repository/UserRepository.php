<?php

declare(strict_types=1);

namespace Rent\Repository;

use PDO;

class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $role, string $fullName, string $email, ?string $phone, ?string $passwordHash, string $createdAt): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO users(role, full_name, email, phone, password_hash, created_at)
            VALUES(:role, :full_name, :email, :phone, :password_hash, :created_at)');
        $stmt->execute([
            'role' => $role,
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'password_hash' => $passwordHash,
            'created_at' => $createdAt,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function ensureDefaultLandlord(array $config, string $createdAt): int
    {
        $email = app_config($config, 'landlord.default_email', 'ids@drmhhh.com');
        $existing = $this->findByEmail($email);
        if ($existing !== null && ($existing['role'] ?? '') === 'landlord') {
            return (int) $existing['id'];
        }

        if ($existing !== null) {
            $stmt = $this->pdo->prepare('UPDATE users SET role = :role WHERE id = :id');
            $stmt->execute(['role' => 'landlord', 'id' => $existing['id']]);
            return (int) $existing['id'];
        }

        return $this->create(
            'landlord',
            app_config($config, 'landlord.default_full_name', 'Системный арендодатель'),
            $email,
            app_config($config, 'landlord.default_phone', '+70000000000'),
            null,
            $createdAt
        );
    }
}
