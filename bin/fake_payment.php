<?php

declare(strict_types=1);

use Rent\Database\Database;
use Rent\Database\Migrator;
use Rent\Repository\ContractRepository;
use Rent\Repository\InvitationRepository;
use Rent\Repository\PaymentRepository;
use Rent\Repository\TenantRepository;
use Rent\Repository\UserRepository;
use Rent\Service\FileStorageService;
use Rent\Service\PdfService;
use Rent\Service\ContentService;
use Rent\Support\View;

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

if (!is_file(dirname(__DIR__) . '/vendor/autoload.php')) {
    fwrite(STDERR, "Dependencies are not installed. Run: composer install\n");
    exit(1);
}

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/src/Support/helpers.php';

$config = require dirname(__DIR__) . '/config.php';
$timezone = (string) app_config($config, 'app.timezone', 'Europe/Moscow');
$now = app_now($timezone)->format('Y-m-d H:i:s');

$args = [];
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--')) {
        $parts = explode('=', substr($arg, 2), 2);
        $args[$parts[0]] = $parts[1] ?? '1';
    }
}

$token = trim((string) ($args['token'] ?? ''));

$pdo = Database::connect((string) app_config($config, 'db.path'));
(new Migrator($pdo))->run();

$invitationRepository = new InvitationRepository($pdo);
$contractRepository = new ContractRepository($pdo);
$userRepository = new UserRepository($pdo);
$tenantRepository = new TenantRepository($pdo);
$paymentRepository = new PaymentRepository($pdo);
$pdfService = new PdfService($config, new View(dirname(__DIR__) . '/templates'), new FileStorageService($config));
$contentService = new ContentService($config);

if ($token === '') {
    $rows = $invitationRepository->listAll();
    if ($rows === []) {
        fwrite(STDERR, "No invitations found.\n");
        exit(1);
    }
    $token = (string) $rows[0]['token'];
}

$invitation = $invitationRepository->findByToken($token);
if ($invitation === null) {
    fwrite(STDERR, "Invitation not found by token: {$token}\n");
    exit(1);
}

$contract = $contractRepository->findByInvitationId((int) $invitation['id']);
if ($contract === null) {
    fwrite(STDERR, "Contract not found for invitation #{$invitation['id']}.\n");
    exit(1);
}

$tenant = $userRepository->findById((int) $contract['tenant_id']);
$tenantProfile = $tenantRepository->findByUserId((int) $contract['tenant_id']);
if ($tenant === null || $tenantProfile === null) {
    fwrite(STDERR, "Tenant profile is incomplete for contract #{$contract['id']}.\n");
    exit(1);
}

$pdo->beginTransaction();

try {
    $contractPdfPath = (string) ($contract['pdf_path'] ?? '');
    if ($contractPdfPath === '') {
        $contractPdfPath = $pdfService->generateContractPdf(buildContractPayload($config, $contentService, $invitation, $tenant, $tenantProfile));
        $contractRepository->updatePdfPath((int) $contract['id'], $contractPdfPath);
    }

    $paymentId = 'fake_' . bin2hex(random_bytes(8));
    $paymentDbId = $paymentRepository->create([
        'contract_id' => (int) $contract['id'],
        'amount' => (float) $invitation['rent_amount'],
        'status' => 'pending',
        'payment_id' => $paymentId,
        'yookassa_status' => 'succeeded',
        'receipt_pdf_path' => null,
        'next_payment_date' => null,
        'paid_at' => null,
        'created_at' => $now,
    ]);

    $receiptPdfPath = $pdfService->generateReceiptPdf([
        'tenant' => $tenant,
        'invitation' => $invitation,
        'payment_id' => $paymentId,
        'amount' => (float) $invitation['rent_amount'],
        'paid_at' => $now,
        'city' => app_config($config, 'app.city', 'Москва'),
        'service_name' => app_config($config, 'app.name', 'ДМаренда'),
    ]);

    $nextPaymentDate = calculateNextPaymentDate($paymentRepository, $contract, (string) $invitation['start_date'], $timezone);
    $paymentRepository->updateSucceeded($paymentDbId, $receiptPdfPath, $nextPaymentDate, $now);
    $invitationRepository->updateStatus((int) $invitation['id'], 'paid');

    $pdo->commit();

    $baseUrl = rtrim((string) app_config($config, 'app.base_url', ''), '/');
    $cabinetUrl = $baseUrl . '/i/' . $token . '/cabinet';

    echo "Fake payment completed.\n";
    echo "Invitation ID: " . (int) $invitation['id'] . "\n";
    echo "Contract ID: " . (int) $contract['id'] . "\n";
    echo "Payment DB ID: " . $paymentDbId . "\n";
    echo "Payment ID: " . $paymentId . "\n";
    echo "Next payment date: " . $nextPaymentDate . "\n";
    echo "Contract PDF: " . $contractPdfPath . "\n";
    echo "Receipt PDF: " . $receiptPdfPath . "\n";
    echo "Cabinet URL: " . $cabinetUrl . "\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Fake payment failed: " . $e->getMessage() . "\n");
    exit(1);
}

function calculateNextPaymentDate(PaymentRepository $paymentRepository, array $contract, string $startDate, string $timezone): string
{
    $tz = new DateTimeZone($timezone);
    $latest = $paymentRepository->latestSucceededByContract((int) $contract['id']);

    if ($latest !== null && !empty($latest['next_payment_date'])) {
        $base = new DateTimeImmutable((string) $latest['next_payment_date'], $tz);
        return $base->modify('+1 month')->format('Y-m-d');
    }

    $start = new DateTimeImmutable($startDate, $tz);
    return $start->modify('+1 month')->format('Y-m-d');
}

function monthNameRu(int $month): string
{
    $months = [
        1 => 'января',
        2 => 'февраля',
        3 => 'марта',
        4 => 'апреля',
        5 => 'мая',
        6 => 'июня',
        7 => 'июля',
        8 => 'августа',
        9 => 'сентября',
        10 => 'октября',
        11 => 'ноября',
        12 => 'декабря',
    ];

    return $months[$month] ?? '';
}

function buildContractPayload(array $config, ContentService $contentService, array $invitation, array $tenant, array $tenantProfile): array
{
    $timezone = (string) app_config($config, 'app.timezone', 'Europe/Moscow');
    $contractDate = app_now($timezone);
    $landlord = app_config($config, 'landlord.details', []);
    $rentAmount = number_format((float) $invitation['rent_amount'], 2, '.', ' ');

    $variables = [
        'city' => (string) app_config($config, 'app.city', 'Москва'),
        'contract_date_day' => $contractDate->format('d'),
        'contract_date_month' => monthNameRu((int) $contractDate->format('n')),
        'contract_date_year' => $contractDate->format('Y'),
        'tenant_full_name' => (string) $tenant['full_name'],
        'tenant_passport_series' => (string) $tenantProfile['passport_series'],
        'tenant_passport_number' => (string) $tenantProfile['passport_number'],
        'tenant_passport_issued_by' => (string) $tenantProfile['passport_issued_by'],
        'tenant_passport_date' => (string) $tenantProfile['passport_date'],
        'tenant_registration_address' => (string) $tenantProfile['registration_address'],
        'tenant_phone' => (string) $tenant['phone'],
        'tenant_email' => (string) $tenant['email'],
        'property_address' => (string) $invitation['property_address'],
        'rent_amount' => $rentAmount,
        'landlord_full_name' => (string) ($landlord['full_name'] ?? ''),
        'landlord_type' => (string) ($landlord['type'] ?? ''),
        'landlord_inn' => (string) ($landlord['inn'] ?? ''),
        'landlord_passport_series' => (string) ($landlord['passport_series'] ?? ''),
        'landlord_passport_number' => (string) ($landlord['passport_number'] ?? ''),
        'landlord_passport_issued_by' => (string) ($landlord['passport_issued_by'] ?? ''),
        'landlord_passport_date' => (string) ($landlord['passport_date'] ?? ''),
        'landlord_registration_address' => (string) ($landlord['registration_address'] ?? ''),
        'landlord_phone' => (string) ($landlord['phone'] ?? ''),
        'landlord_email' => (string) ($landlord['email'] ?? ''),
    ];

    return [
        'city' => $variables['city'],
        'contract_date' => $contractDate->format('Y-m-d'),
        'contract_date_day' => $variables['contract_date_day'],
        'contract_date_month' => $variables['contract_date_month'],
        'contract_date_year' => $variables['contract_date_year'],
        'tenant_full_name' => $variables['tenant_full_name'],
        'tenant_passport_series' => $variables['tenant_passport_series'],
        'tenant_passport_number' => $variables['tenant_passport_number'],
        'tenant_passport_issued_by' => $variables['tenant_passport_issued_by'],
        'tenant_passport_date' => $variables['tenant_passport_date'],
        'tenant_registration_address' => $variables['tenant_registration_address'],
        'tenant_phone' => $variables['tenant_phone'],
        'tenant_email' => $variables['tenant_email'],
        'property_address' => $variables['property_address'],
        'rent_amount' => $variables['rent_amount'],
        'landlord' => $landlord,
        'tenant' => $tenant,
        'tenant_profile' => $tenantProfile,
        'invitation' => $invitation,
        'service_name' => app_config($config, 'app.name', 'ДМаренда'),
        'contract_html' => $contentService->renderContractHtml($variables),
    ];
}
