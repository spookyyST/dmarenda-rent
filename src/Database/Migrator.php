<?php

declare(strict_types=1);

namespace Rent\Database;

use PDO;

class Migrator
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function run(): void
    {
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            role TEXT NOT NULL CHECK(role IN ("admin", "landlord", "tenant")),
            full_name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            phone TEXT,
            password_hash TEXT,
            created_at TEXT NOT NULL
        )');

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS invitations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            landlord_id INTEGER NOT NULL,
            email TEXT NOT NULL,
            phone TEXT,
            property_address TEXT NOT NULL,
            rent_amount REAL NOT NULL,
            start_date TEXT NOT NULL,
            token TEXT NOT NULL UNIQUE,
            status TEXT NOT NULL CHECK(status IN ("new", "registered", "paid")) DEFAULT "new",
            created_at TEXT NOT NULL,
            FOREIGN KEY (landlord_id) REFERENCES users(id)
        )');

        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_invitations_token ON invitations(token)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_invitations_status ON invitations(status)');

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS tenants (
            user_id INTEGER PRIMARY KEY,
            passport_series TEXT NOT NULL,
            passport_number TEXT NOT NULL,
            passport_issued_by TEXT NOT NULL,
            passport_date TEXT NOT NULL,
            registration_address TEXT NOT NULL,
            passport_scan_main TEXT NOT NULL,
            passport_scan_address TEXT NOT NULL,
            consent_pd_at TEXT NOT NULL,
            consent_policy_at TEXT NOT NULL,
            consent_contract_at TEXT NOT NULL,
            consent_ip TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )');

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS contracts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            invitation_id INTEGER NOT NULL UNIQUE,
            landlord_id INTEGER NOT NULL,
            tenant_id INTEGER NOT NULL,
            pdf_path TEXT,
            created_at TEXT NOT NULL,
            FOREIGN KEY (invitation_id) REFERENCES invitations(id),
            FOREIGN KEY (landlord_id) REFERENCES users(id),
            FOREIGN KEY (tenant_id) REFERENCES users(id)
        )');

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            contract_id INTEGER NOT NULL,
            amount REAL NOT NULL,
            status TEXT NOT NULL,
            payment_id TEXT NOT NULL UNIQUE,
            yookassa_status TEXT,
            receipt_pdf_path TEXT,
            next_payment_date TEXT,
            paid_at TEXT,
            created_at TEXT NOT NULL,
            FOREIGN KEY (contract_id) REFERENCES contracts(id)
        )');

        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_payments_contract_status ON payments(contract_id, status)');

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            contract_id INTEGER,
            tenant_id INTEGER,
            type TEXT NOT NULL,
            channel TEXT NOT NULL,
            recipient TEXT NOT NULL,
            payload_json TEXT,
            status TEXT NOT NULL,
            scheduled_for TEXT,
            sent_at TEXT,
            created_at TEXT NOT NULL,
            FOREIGN KEY (contract_id) REFERENCES contracts(id),
            FOREIGN KEY (tenant_id) REFERENCES users(id)
        )');

        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_notifications_dedupe ON notifications(contract_id, type, scheduled_for, status)');

        $this->pdo->exec('CREATE TABLE IF NOT EXISTS webhook_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_id TEXT NOT NULL UNIQUE,
            event_type TEXT NOT NULL,
            payment_id TEXT,
            received_at TEXT NOT NULL
        )');

        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_webhook_events_payment_id ON webhook_events(payment_id)');
    }
}
