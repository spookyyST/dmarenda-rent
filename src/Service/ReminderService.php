<?php

declare(strict_types=1);

namespace Rent\Service;

use DateTimeImmutable;
use DateTimeZone;
use Rent\Repository\NotificationRepository;
use Rent\Repository\PaymentRepository;

class ReminderService
{
    public function __construct(
        private readonly array $config,
        private readonly PaymentRepository $paymentRepository,
        private readonly NotificationRepository $notificationRepository,
        private readonly NotificationService $notificationService
    ) {
    }

    public function sendFiveDaysReminders(): array
    {
        $timezone = new DateTimeZone((string) app_config($this->config, 'app.timezone', 'Europe/Moscow'));
        $targetDate = (new DateTimeImmutable('now', $timezone))->modify('+5 days')->format('Y-m-d');

        $duePayments = $this->paymentRepository->findDueReminders($targetDate);

        $sent = 0;
        $skipped = 0;

        foreach ($duePayments as $payment) {
            $contractId = (int) $payment['contract_id'];

            if ($this->notificationRepository->existsSentReminder($contractId, $targetDate)) {
                $skipped++;
                continue;
            }

            $ok = $this->notificationService->sendReminderEmail($payment, $targetDate);
            if ($ok) {
                $sent++;
            }
        }

        return [
            'target_date' => $targetDate,
            'total_due' => count($duePayments),
            'sent' => $sent,
            'skipped' => $skipped,
        ];
    }
}
