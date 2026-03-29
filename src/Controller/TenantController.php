<?php

declare(strict_types=1);

namespace Rent\Controller;

use Rent\Http\Request;
use Rent\Http\Response;
use Rent\Http\Session;
use Rent\Repository\ContractRepository;
use Rent\Repository\InvitationRepository;
use Rent\Repository\PaymentRepository;
use Rent\Repository\TenantRepository;
use Rent\Repository\UserRepository;
use Rent\Service\ContentService;
use Rent\Service\FileStorageService;
use Rent\Service\PaymentWorkflowService;
use Rent\Service\TenantRegistrationService;
use Rent\Support\Auth;
use Rent\Support\Csrf;
use Rent\Support\Validator;
use Rent\Support\View;

class TenantController extends BaseController
{
    public function __construct(
        array $config,
        View $view,
        Session $session,
        Csrf $csrf,
        private readonly Auth $auth,
        private readonly InvitationRepository $invitationRepository,
        private readonly ContractRepository $contractRepository,
        private readonly UserRepository $userRepository,
        private readonly TenantRepository $tenantRepository,
        private readonly FileStorageService $fileStorage,
        private readonly TenantRegistrationService $registrationService,
        private readonly PaymentWorkflowService $paymentWorkflowService,
        private readonly PaymentRepository $paymentRepository,
        private readonly ContentService $contentService
    ) {
        parent::__construct($config, $view, $session, $csrf);
    }

    public function showRegistration(array $params): Response
    {
        $invitation = $this->invitationByParams($params);
        if ($invitation === null) {
            return Response::html('<h1>Invalid invitation</h1>', 404);
        }

        [$tenant, $tenantProfile] = $this->resolveTenantData($invitation);

        $oldInput = $this->session?->getFlash('old_input', []) ?? [];

        return $this->render('tenant/register.php', [
            'invitation' => $invitation,
            'tenant' => $tenant,
            'tenant_profile' => $tenantProfile,
            'is_registered' => $tenant !== null && $tenantProfile !== null,
            'old' => is_array($oldInput) ? $oldInput : [],
            'privacy_url' => $this->assetUrl((string) app_config($this->config, 'app.privacy_path', '/privacy')),
        ], 'Регистрация арендатора');
    }

    public function register(Request $request, array $params): Response
    {
        $invitation = $this->invitationByParams($params);
        if ($invitation === null) {
            return Response::html('<h1>Invalid invitation</h1>', 404);
        }

        try {
            $this->requireCsrf((string) $request->input('_csrf', ''));
        } catch (\RuntimeException) {
            $this->session?->flash('error', 'Сессия устарела. Обновите страницу.');
            return $this->redirect('/i/' . $invitation['token']);
        }

        $input = [
            'full_name' => trim((string) $request->input('full_name', '')),
            'phone' => trim((string) $request->input('phone', '')),
            'email' => trim((string) $request->input('email', '')),
            'passport_series' => trim((string) $request->input('passport_series', '')),
            'passport_number' => trim((string) $request->input('passport_number', '')),
            'passport_issued_by' => trim((string) $request->input('passport_issued_by', '')),
            'passport_date' => trim((string) $request->input('passport_date', '')),
            'registration_address' => trim((string) $request->input('registration_address', '')),
        ];

        $errors = [];

        foreach (['full_name', 'phone', 'email', 'passport_series', 'passport_number', 'passport_issued_by', 'passport_date', 'registration_address'] as $field) {
            if (!Validator::required($input[$field])) {
                $errors[] = 'Поле ' . $field . ' обязательно.';
            }
        }

        if (!Validator::email($input['email'])) {
            $errors[] = 'Некорректный email.';
        }
        if (strtolower($input['email']) !== strtolower((string) $invitation['email'])) {
            $errors[] = 'Email должен совпадать с email в приглашении.';
        }
        if (!Validator::phone($input['phone'])) {
            $errors[] = 'Некорректный телефон.';
        }
        if (!Validator::date($input['passport_date'])) {
            $errors[] = 'Некорректная дата выдачи паспорта.';
        }

        $consentPd = (string) $request->input('consent_pd', '');
        $consentContract = (string) $request->input('consent_contract', '');

        if ($consentPd !== '1' || $consentContract !== '1') {
            $errors[] = 'Необходимо подтвердить согласие на обработку ПД и согласие с условиями договора.';
        }

        if ($errors !== []) {
            $this->session?->flash('error', implode(' ', $errors));
            $this->session?->flash('old_input', $input);
            return $this->redirect('/i/' . $invitation['token']);
        }

        try {
            $this->registrationService->registerByInvitation(
                $invitation,
                $input,
                $request->files(),
                $request->ip()
            );
        } catch (\Throwable $e) {
            $this->session?->flash('error', $e->getMessage());
            return $this->redirect('/i/' . $invitation['token']);
        }

        $this->session?->flash('success', 'Регистрация завершена. Проверьте договор перед оплатой.');

        return $this->redirect('/i/' . $invitation['token'] . '/contract');
    }

    public function showContract(array $params): Response
    {
        $invitation = $this->invitationByParams($params);
        if ($invitation === null) {
            return Response::html('<h1>Invalid invitation</h1>', 404);
        }

        // После успешной оплаты вся работа идет из личного кабинета.
        if ((string) ($invitation['status'] ?? '') === 'paid') {
            return $this->redirect('/i/' . $invitation['token'] . '/cabinet');
        }

        $contract = $this->contractRepository->findByInvitationId((int) $invitation['id']);
        if ($contract === null) {
            return $this->redirect('/i/' . $invitation['token']);
        }

        $tenant = $this->userRepository->findById((int) $contract['tenant_id']);
        $tenantProfile = $this->tenantRepository->findByUserId((int) $contract['tenant_id']);
        if ($tenant === null || $tenantProfile === null) {
            return $this->redirect('/i/' . $invitation['token']);
        }

        $contractHtml = $this->contentService->renderContractHtml($this->buildContractVariables($invitation, $tenant, $tenantProfile));

        return $this->render('tenant/contract_preview.php', [
            'invitation' => $invitation,
            'contract' => $contract,
            'contract_html' => $contractHtml,
            'privacy_url' => $this->assetUrl((string) app_config($this->config, 'app.privacy_path', '/privacy')),
        ], 'Проверка договора');
    }

    public function pay(Request $request, array $params): Response
    {
        $invitation = $this->invitationByParams($params);
        if ($invitation === null) {
            return Response::html('<h1>Invalid invitation</h1>', 404);
        }

        $contract = $this->contractRepository->findByInvitationId((int) $invitation['id']);
        if ($contract === null) {
            $this->session?->flash('error', 'Сначала завершите регистрацию по приглашению.');
            return $this->redirect('/i/' . $invitation['token']);
        }

        $isFirstPayment = $this->paymentRepository->latestSucceededByContract((int) $contract['id']) === null;

        if ($isFirstPayment) {
            if ($request->method() !== 'POST') {
                $this->session?->flash('error', 'Перед первой оплатой подтвердите согласия под договором.');
                return $this->redirect('/i/' . $invitation['token'] . '/contract');
            }

            try {
                $this->requireCsrf((string) $request->input('_csrf', ''));
            } catch (\RuntimeException) {
                $this->session?->flash('error', 'Сессия устарела. Обновите страницу договора.');
                return $this->redirect('/i/' . $invitation['token'] . '/contract');
            }

            $consentPd = (string) $request->input('consent_pd', '');
            $consentContract = (string) $request->input('consent_contract', '');
            if ($consentPd !== '1' || $consentContract !== '1') {
                $this->session?->flash('error', 'Подтвердите согласие на обработку ПД и согласие с договором.');
                return $this->redirect('/i/' . $invitation['token'] . '/contract');
            }
        }

        try {
            $payment = $this->paymentWorkflowService->createPaymentForInvitation($invitation);
            return Response::redirect((string) $payment['confirmation_url']);
        } catch (\Throwable $e) {
            $this->session?->flash('error', 'Не удалось создать платёж: ' . $e->getMessage());
            $fallbackPath = $isFirstPayment ? '/i/' . $invitation['token'] . '/contract' : '/i/' . $invitation['token'] . '/cabinet';
            return $this->redirect($fallbackPath);
        }
    }

    public function paymentReturn(array $params): Response
    {
        $invitation = $this->invitationByParams($params);
        if ($invitation === null) {
            return Response::html('<h1>Invalid invitation</h1>', 404);
        }

        $contract = $this->contractRepository->findByInvitationId((int) $invitation['id']);
        $latestPayment = null;

        if ($contract !== null) {
            $latestPayment = $this->paymentRepository->latestByContract((int) $contract['id']);
        }

        return $this->render('tenant/payment_return.php', [
            'invitation' => $invitation,
            'latest_payment' => $latestPayment,
        ], 'Статус оплаты');
    }

    public function fakePayConfirm(array $params): Response
    {
        $invitation = $this->invitationByParams($params);
        if ($invitation === null) {
            return Response::html('<h1>Invalid invitation</h1>', 404);
        }

        $paymentId = (string) ($params['paymentId'] ?? '');
        if ($paymentId === '') {
            $this->session?->flash('error', 'Не найден тестовый платеж.');
            return $this->redirect('/i/' . $invitation['token'] . '/contract');
        }

        try {
            $this->paymentWorkflowService->processFakeConfirmation((string) $invitation['token'], $paymentId);
            $this->session?->flash('success', 'Тестовая оплата успешно выполнена.');
        } catch (\Throwable $e) {
            $this->session?->flash('error', 'Тестовая оплата не удалась: ' . $e->getMessage());
            return $this->redirect('/i/' . $invitation['token'] . '/contract');
        }

        return $this->redirect('/i/' . $invitation['token'] . '/pay/return');
    }

    public function showCabinet(array $params): Response
    {
        $invitation = $this->invitationByParams($params);
        if ($invitation === null) {
            return Response::html('<h1>Invalid invitation</h1>', 404);
        }

        $contract = $this->contractRepository->findByInvitationId((int) $invitation['id']);
        if ($contract === null) {
            return $this->redirect('/i/' . $invitation['token']);
        }

        $tenant = $this->userRepository->findById((int) $contract['tenant_id']);
        $tenantProfile = $this->tenantRepository->findByUserId((int) $contract['tenant_id']);
        if ($tenant === null || $tenantProfile === null) {
            return $this->redirect('/i/' . $invitation['token']);
        }

        $latestPayment = $this->paymentRepository->latestByContract((int) $contract['id']);
        $paymentHistory = $this->paymentRepository->listByContract((int) $contract['id']);

        return $this->render('tenant/cabinet.php', [
            'invitation' => $invitation,
            'contract' => $contract,
            'tenant' => $tenant,
            'tenant_profile' => $tenantProfile,
            'latest_payment' => $latestPayment,
            'payment_history' => $paymentHistory,
        ], 'Личный кабинет арендатора');
    }

    public function downloadContract(array $params): Response
    {
        $invitation = $this->invitationByParams($params);
        if ($invitation === null) {
            return Response::html('<h1>Invalid invitation</h1>', 404);
        }

        $contract = $this->contractRepository->findByInvitationId((int) $invitation['id']);
        if ($contract === null || empty($contract['pdf_path'])) {
            return Response::html('<h1>Contract file not found</h1>', 404);
        }

        $relativePath = (string) $contract['pdf_path'];
        $absolutePath = $this->fileStorage->absolutePath($relativePath);
        if (!is_file($absolutePath)) {
            return Response::html('<h1>Contract file not found</h1>', 404);
        }

        $content = (string) file_get_contents($absolutePath);
        return new Response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="contract_' . (int) $contract['id'] . '.pdf"',
            'Content-Length' => (string) filesize($absolutePath),
        ]);
    }

    public function downloadReceipt(array $params): Response
    {
        $invitation = $this->invitationByParams($params);
        if ($invitation === null) {
            return Response::html('<h1>Invalid invitation</h1>', 404);
        }

        $contract = $this->contractRepository->findByInvitationId((int) $invitation['id']);
        if ($contract === null) {
            return Response::html('<h1>Contract not found</h1>', 404);
        }

        $paymentId = (int) ($params['paymentId'] ?? 0);
        if ($paymentId <= 0) {
            return Response::html('<h1>Receipt not found</h1>', 404);
        }

        $payment = $this->paymentRepository->findById($paymentId);
        if ($payment === null || (int) $payment['contract_id'] !== (int) $contract['id'] || empty($payment['receipt_pdf_path'])) {
            return Response::html('<h1>Receipt not found</h1>', 404);
        }

        $relativePath = (string) $payment['receipt_pdf_path'];
        $absolutePath = $this->fileStorage->absolutePath($relativePath);
        if (!is_file($absolutePath)) {
            return Response::html('<h1>Receipt not found</h1>', 404);
        }

        $content = (string) file_get_contents($absolutePath);
        return new Response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="receipt_' . $paymentId . '.pdf"',
            'Content-Length' => (string) filesize($absolutePath),
        ]);
    }

    private function invitationByParams(array $params): ?array
    {
        $token = (string) ($params['token'] ?? '');
        if ($token === '') {
            return null;
        }

        return $this->invitationRepository->findByToken($token);
    }

    private function resolveTenantData(array $invitation): array
    {
        $contract = $this->contractRepository->findByInvitationId((int) $invitation['id']);
        if ($contract === null) {
            return [null, null];
        }

        $tenant = $this->userRepository->findById((int) $contract['tenant_id']);
        $tenantProfile = $this->tenantRepository->findByUserId((int) $contract['tenant_id']);

        return [$tenant, $tenantProfile];
    }

    private function buildContractVariables(array $invitation, array $tenant, array $tenantProfile): array
    {
        $now = app_now((string) app_config($this->config, 'app.timezone', 'Europe/Moscow'));
        $landlord = app_config($this->config, 'landlord.details', []);

        return [
            'city' => (string) app_config($this->config, 'app.city', 'Москва'),
            'contract_date_day' => $now->format('d'),
            'contract_date_month' => $this->monthNameRu((int) $now->format('n')),
            'contract_date_year' => $now->format('Y'),
            'tenant_full_name' => (string) ($tenant['full_name'] ?? ''),
            'tenant_passport_series' => (string) ($tenantProfile['passport_series'] ?? ''),
            'tenant_passport_number' => (string) ($tenantProfile['passport_number'] ?? ''),
            'tenant_passport_issued_by' => (string) ($tenantProfile['passport_issued_by'] ?? ''),
            'tenant_passport_date' => (string) ($tenantProfile['passport_date'] ?? ''),
            'tenant_registration_address' => (string) ($tenantProfile['registration_address'] ?? ''),
            'tenant_phone' => (string) ($tenant['phone'] ?? ''),
            'tenant_email' => (string) ($tenant['email'] ?? ''),
            'property_address' => (string) ($invitation['property_address'] ?? ''),
            'rent_amount' => number_format((float) ($invitation['rent_amount'] ?? 0), 2, '.', ' '),
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
    }

    private function monthNameRu(int $month): string
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

}
