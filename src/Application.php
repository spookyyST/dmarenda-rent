<?php

declare(strict_types=1);

namespace Rent;

use PDO;
use Rent\Controller\AdminAuthController;
use Rent\Controller\AdminController;
use Rent\Controller\LegalController;
use Rent\Controller\TenantController;
use Rent\Controller\WebhookController;
use Rent\Database\Database;
use Rent\Database\Migrator;
use Rent\Http\Request;
use Rent\Http\Response;
use Rent\Http\Router;
use Rent\Http\Session;
use Rent\Repository\ContractRepository;
use Rent\Repository\InvitationRepository;
use Rent\Repository\NotificationRepository;
use Rent\Repository\PaymentRepository;
use Rent\Repository\TenantRepository;
use Rent\Repository\UserRepository;
use Rent\Repository\WebhookEventRepository;
use Rent\Service\FileStorageService;
use Rent\Service\MailService;
use Rent\Service\ContentService;
use Rent\Service\NotificationService;
use Rent\Service\PaymentWorkflowService;
use Rent\Service\PdfService;
use Rent\Service\ReminderService;
use Rent\Service\TenantRegistrationService;
use Rent\Service\YookassaService;
use Rent\Support\Auth;
use Rent\Support\Csrf;
use Rent\Support\Logger;
use Rent\Support\View;

class Application
{
    private PDO $pdo;
    private View $view;
    private Logger $logger;
    private ?Session $session;
    private ?Csrf $csrf;
    private ?Auth $auth;

    private UserRepository $userRepository;
    private InvitationRepository $invitationRepository;
    private TenantRepository $tenantRepository;
    private ContractRepository $contractRepository;
    private PaymentRepository $paymentRepository;
    private NotificationRepository $notificationRepository;
    private WebhookEventRepository $webhookEventRepository;

    private FileStorageService $fileStorage;
    private MailService $mailService;
    private ContentService $contentService;
    private NotificationService $notificationService;
    private PdfService $pdfService;
    private YookassaService $yookassaService;
    private TenantRegistrationService $tenantRegistrationService;
    private PaymentWorkflowService $paymentWorkflowService;
    private ReminderService $reminderService;

    public function __construct(private readonly array $config, bool $withSession = true)
    {
        date_default_timezone_set((string) app_config($this->config, 'app.timezone', 'Europe/Moscow'));

        $this->prepareDirectories();
        $this->pdo = Database::connect((string) app_config($this->config, 'db.path'));

        (new Migrator($this->pdo))->run();

        $this->view = new View(dirname(__DIR__) . '/templates');
        $logFile = rtrim((string) app_config($this->config, 'storage.logs_dir'), '/') . '/app.log';
        $this->logger = new Logger($logFile);

        if ($withSession) {
            $this->session = new Session();
            $this->csrf = new Csrf($this->session);
            $this->auth = new Auth($this->session);
        } else {
            $this->session = null;
            $this->csrf = null;
            $this->auth = null;
        }

        $this->wireRepositories();
        $this->wireServices();

        $this->userRepository->ensureDefaultLandlord(
            $this->config,
            app_now((string) app_config($this->config, 'app.timezone'))->format('Y-m-d H:i:s')
        );
    }

    public function router(): Router
    {
        if ($this->session === null || $this->csrf === null || $this->auth === null) {
            throw new \RuntimeException('Router requires session-enabled application.');
        }

        $router = new Router();

        $adminAuthController = new AdminAuthController(
            $this->config,
            $this->view,
            $this->session,
            $this->csrf,
            $this->auth
        );

        $adminController = new AdminController(
            $this->config,
            $this->view,
            $this->session,
            $this->csrf,
            $this->auth,
            $this->userRepository,
            $this->invitationRepository,
            $this->contractRepository,
            $this->paymentRepository,
            $this->tenantRepository,
            $this->fileStorage,
            $this->contentService,
            $this->notificationService
        );

        $tenantController = new TenantController(
            $this->config,
            $this->view,
            $this->session,
            $this->csrf,
            $this->auth,
            $this->invitationRepository,
            $this->contractRepository,
            $this->userRepository,
            $this->tenantRepository,
            $this->fileStorage,
            $this->tenantRegistrationService,
            $this->paymentWorkflowService,
            $this->paymentRepository,
            $this->contentService
        );

        $webhookController = new WebhookController($this->paymentWorkflowService, $this->logger);
        $legalController = new LegalController($this->config, $this->view, $this->contentService);

        $router->get('/', fn (): Response => Response::redirect(rtrim((string) app_config($this->config, 'app.base_path', '/rent'), '/') . '/admin/login'));

        $router->get('/admin/login', fn (): Response => $adminAuthController->showLogin());
        $router->post('/admin/login', fn (Request $request): Response => $adminAuthController->login($request));
        $router->get('/admin/logout', fn (): Response => $adminAuthController->logout());

        $router->get('/admin/invitations', fn (Request $request): Response => $adminController->listInvitations($request));
        $router->get('/admin/invitations/new', fn (): Response => $adminController->showCreateInvitation());
        $router->post('/admin/invitations/new', fn (Request $request): Response => $adminController->createInvitation($request));

        $router->get('/admin/contracts', fn (Request $request): Response => $adminController->listContracts($request));
        $router->get('/admin/payments', fn (): Response => $adminController->listPayments());
        $router->get('/admin/content', fn (): Response => $adminController->showContentEditor());
        $router->post('/admin/content', fn (Request $request): Response => $adminController->saveContentEditor($request));
        $router->get('/admin/tenant/{id}', fn (Request $request, array $params): Response => $adminController->showTenant($params));
        $router->get('/admin/download/contract/{id}', fn (Request $request, array $params): Response => $adminController->downloadContract($params));
        $router->get('/admin/download/receipt/{paymentId}', fn (Request $request, array $params): Response => $adminController->downloadReceipt($params));

        $router->get('/i/{token}', fn (Request $request, array $params): Response => $tenantController->showRegistration($params));
        $router->post('/i/{token}', fn (Request $request, array $params): Response => $tenantController->register($request, $params));
        $router->get('/i/{token}/contract', fn (Request $request, array $params): Response => $tenantController->showContract($params));
        $router->get('/i/{token}/cabinet', fn (Request $request, array $params): Response => $tenantController->showCabinet($params));
        $router->get('/i/{token}/pay', fn (Request $request, array $params): Response => $tenantController->pay($request, $params));
        $router->post('/i/{token}/pay', fn (Request $request, array $params): Response => $tenantController->pay($request, $params));
        $router->get('/i/{token}/pay/return', fn (Request $request, array $params): Response => $tenantController->paymentReturn($params));
        $router->get('/i/{token}/download/contract', fn (Request $request, array $params): Response => $tenantController->downloadContract($params));
        $router->get('/i/{token}/download/receipt/{paymentId}', fn (Request $request, array $params): Response => $tenantController->downloadReceipt($params));

        $router->post('/webhooks/yookassa', fn (Request $request): Response => $webhookController->yookassa($request));

        $router->get('/privacy', fn (): Response => $legalController->privacy());

        return $router;
    }

    public function reminderService(): ReminderService
    {
        return $this->reminderService;
    }

    public function logger(): Logger
    {
        return $this->logger;
    }

    private function wireRepositories(): void
    {
        $this->userRepository = new UserRepository($this->pdo);
        $this->invitationRepository = new InvitationRepository($this->pdo);
        $this->tenantRepository = new TenantRepository($this->pdo);
        $this->contractRepository = new ContractRepository($this->pdo);
        $this->paymentRepository = new PaymentRepository($this->pdo);
        $this->notificationRepository = new NotificationRepository($this->pdo);
        $this->webhookEventRepository = new WebhookEventRepository($this->pdo);
    }

    private function wireServices(): void
    {
        $this->fileStorage = new FileStorageService($this->config);
        $this->mailService = new MailService($this->config, $this->logger, $this->fileStorage);
        $this->contentService = new ContentService($this->config);
        $this->notificationService = new NotificationService($this->config, $this->mailService, $this->notificationRepository, $this->view);
        $this->pdfService = new PdfService($this->config, $this->view, $this->fileStorage);
        $this->yookassaService = new YookassaService($this->config);

        $this->tenantRegistrationService = new TenantRegistrationService(
            $this->config,
            $this->pdo,
            $this->userRepository,
            $this->tenantRepository,
            $this->invitationRepository,
            $this->contractRepository,
            $this->fileStorage,
            $this->notificationService,
            $this->pdfService,
            $this->contentService
        );

        $this->paymentWorkflowService = new PaymentWorkflowService(
            $this->config,
            $this->pdo,
            $this->yookassaService,
            $this->paymentRepository,
            $this->contractRepository,
            $this->invitationRepository,
            $this->userRepository,
            $this->tenantRepository,
            $this->webhookEventRepository,
            $this->pdfService,
            $this->notificationService,
            $this->contentService
        );

        $this->reminderService = new ReminderService(
            $this->config,
            $this->paymentRepository,
            $this->notificationRepository,
            $this->notificationService
        );
    }

    private function prepareDirectories(): void
    {
        $dirs = [
            app_config($this->config, 'storage.logs_dir'),
            dirname((string) app_config($this->config, 'db.path')),
            app_config($this->config, 'storage.passports_dir'),
            app_config($this->config, 'storage.contracts_dir'),
            app_config($this->config, 'storage.receipts_dir'),
            app_config($this->config, 'storage.content_dir'),
        ];

        foreach ($dirs as $dir) {
            if (!is_string($dir) || $dir === '') {
                continue;
            }
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
        }
    }
}
