<?php

declare(strict_types=1);

namespace Rent\Controller;

use RuntimeException;
use Rent\Http\Request;
use Rent\Http\Response;
use Rent\Http\Session;
use Rent\Repository\ContractRepository;
use Rent\Repository\InvitationRepository;
use Rent\Repository\PaymentRepository;
use Rent\Repository\TenantRepository;
use Rent\Repository\UserRepository;
use Rent\Service\FileStorageService;
use Rent\Support\Auth;
use Rent\Support\Csrf;
use Rent\Support\Validator;
use Rent\Support\View;

class AdminController extends BaseController
{
    public function __construct(
        array $config,
        View $view,
        Session $session,
        Csrf $csrf,
        private readonly Auth $auth,
        private readonly UserRepository $userRepository,
        private readonly InvitationRepository $invitationRepository,
        private readonly ContractRepository $contractRepository,
        private readonly PaymentRepository $paymentRepository,
        private readonly TenantRepository $tenantRepository,
        private readonly FileStorageService $fileStorage
    ) {
        parent::__construct($config, $view, $session, $csrf);
    }

    public function listInvitations(Request $request): Response
    {
        if (($guard = $this->guard()) !== null) {
            return $guard;
        }

        $status = $request->query('status');
        $status = is_string($status) ? $status : null;

        $invitations = $this->invitationRepository->listAll($status);

        return $this->render('admin/invitations.php', [
            'invitations' => $invitations,
            'selected_status' => $status,
            'base_url' => app_config($this->config, 'app.base_url'),
        ], 'Приглашения');
    }

    public function showCreateInvitation(): Response
    {
        if (($guard = $this->guard()) !== null) {
            return $guard;
        }

        return $this->render('admin/invitation_create.php', [], 'Новое приглашение');
    }

    public function createInvitation(Request $request): Response
    {
        if (($guard = $this->guard()) !== null) {
            return $guard;
        }

        try {
            $this->requireCsrf((string) $request->input('_csrf', ''));
        } catch (RuntimeException) {
            $this->session?->flash('error', 'Сессия устарела.');
            return $this->redirect('/admin/invitations/new');
        }

        $email = trim((string) $request->input('email', ''));
        $phone = trim((string) $request->input('phone', ''));
        $propertyAddress = trim((string) $request->input('property_address', ''));
        $rentAmount = trim((string) $request->input('rent_amount', ''));
        $startDate = trim((string) $request->input('start_date', ''));

        $errors = [];

        if (!Validator::email($email)) {
            $errors[] = 'Некорректный email арендатора.';
        }
        if ($phone !== '' && !Validator::phone($phone)) {
            $errors[] = 'Некорректный телефон.';
        }
        if (!Validator::required($propertyAddress)) {
            $errors[] = 'Укажите адрес объекта.';
        }
        if (!Validator::positiveAmount($rentAmount)) {
            $errors[] = 'Сумма аренды должна быть больше нуля.';
        }
        if (!Validator::date($startDate)) {
            $errors[] = 'Некорректная дата начала аренды.';
        }

        if ($errors !== []) {
            $this->session?->flash('error', implode(' ', $errors));
            return $this->redirect('/admin/invitations/new');
        }

        $landlordId = $this->userRepository->ensureDefaultLandlord(
            $this->config,
            app_now((string) app_config($this->config, 'app.timezone'))->format('Y-m-d H:i:s')
        );

        $token = bin2hex(random_bytes(24));

        $this->invitationRepository->create([
            'landlord_id' => $landlordId,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'property_address' => $propertyAddress,
            'rent_amount' => (float) $rentAmount,
            'start_date' => $startDate,
            'token' => $token,
            'status' => 'new',
            'created_at' => app_now((string) app_config($this->config, 'app.timezone'))->format('Y-m-d H:i:s'),
        ]);

        $this->session?->flash(
            'success',
            'Приглашение создано. Ссылка: ' . rtrim((string) app_config($this->config, 'app.base_url'), '/') . '/i/' . $token
        );

        return $this->redirect('/admin/invitations');
    }

    public function listContracts(Request $request): Response
    {
        if (($guard = $this->guard()) !== null) {
            return $guard;
        }

        $status = $request->query('status');
        $status = is_string($status) ? $status : null;
        $contracts = $this->contractRepository->listAll($status);

        return $this->render('admin/contracts.php', [
            'contracts' => $contracts,
            'selected_status' => $status,
        ], 'Договоры');
    }

    public function listPayments(): Response
    {
        if (($guard = $this->guard()) !== null) {
            return $guard;
        }

        $payments = $this->paymentRepository->listAll();

        return $this->render('admin/payments.php', [
            'payments' => $payments,
        ], 'Платежи');
    }

    public function showTenant(array $params): Response
    {
        if (($guard = $this->guard()) !== null) {
            return $guard;
        }

        $tenantId = (int) ($params['id'] ?? 0);
        if ($tenantId <= 0) {
            return Response::html('<h1>Tenant not found</h1>', 404);
        }

        $tenant = $this->userRepository->findById($tenantId);
        $tenantProfile = $tenant !== null ? $this->tenantRepository->findByUserId($tenantId) : null;

        if ($tenant === null || $tenantProfile === null) {
            return Response::html('<h1>Tenant not found</h1>', 404);
        }

        return $this->render('admin/tenant.php', [
            'tenant' => $tenant,
            'tenant_profile' => $tenantProfile,
        ], 'Данные арендатора');
    }

    public function downloadContract(array $params): Response
    {
        if (($guard = $this->guard()) !== null) {
            return $guard;
        }

        $contractId = (int) ($params['id'] ?? 0);
        $contract = $this->contractRepository->findById($contractId);

        if ($contract === null || empty($contract['pdf_path'])) {
            return Response::html('<h1>Contract file not found</h1>', 404);
        }

        return $this->downloadFile((string) $contract['pdf_path'], 'contract_' . $contractId . '.pdf');
    }

    public function downloadReceipt(array $params): Response
    {
        if (($guard = $this->guard()) !== null) {
            return $guard;
        }

        $paymentId = (int) ($params['paymentId'] ?? 0);
        if ($paymentId <= 0) {
            return Response::html('<h1>Receipt not found</h1>', 404);
        }

        $target = $this->paymentRepository->findById($paymentId);

        if ($target === null || empty($target['receipt_pdf_path'])) {
            return Response::html('<h1>Receipt not found</h1>', 404);
        }

        return $this->downloadFile((string) $target['receipt_pdf_path'], 'receipt_' . $paymentId . '.pdf');
    }

    private function downloadFile(string $relativePath, string $downloadName): Response
    {
        $absolutePath = $this->fileStorage->absolutePath($relativePath);
        if (!is_file($absolutePath)) {
            return Response::html('<h1>File not found</h1>', 404);
        }

        $content = (string) file_get_contents($absolutePath);

        return new Response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
            'Content-Length' => (string) filesize($absolutePath),
        ]);
    }

    private function guard(): ?Response
    {
        if (!$this->auth->isAdmin()) {
            return $this->redirect('/admin/login');
        }

        return null;
    }
}
