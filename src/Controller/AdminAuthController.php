<?php

declare(strict_types=1);

namespace Rent\Controller;

use Rent\Http\Request;
use Rent\Http\Response;
use Rent\Http\Session;
use Rent\Support\Auth;
use Rent\Support\Csrf;
use Rent\Support\View;

class AdminAuthController extends BaseController
{
    public function __construct(
        array $config,
        View $view,
        Session $session,
        Csrf $csrf,
        private readonly Auth $auth
    ) {
        parent::__construct($config, $view, $session, $csrf);
    }

    public function showLogin(): Response
    {
        if ($this->auth->isAdmin()) {
            return $this->redirect('/admin/invitations');
        }

        return $this->render('admin/login.php', [], 'Вход администратора');
    }

    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 900; // 15 минут

    public function login(Request $request): Response
    {
        try {
            $this->requireCsrf((string) $request->input('_csrf', ''));
        } catch (\RuntimeException $e) {
            $this->session?->flash('error', 'Сессия устарела. Обновите страницу.');
            return $this->redirect('/admin/login');
        }

        // Проверка блокировки
        $lockUntil = (int) ($this->session?->get('admin_lock_until', 0) ?? 0);
        if ($lockUntil > time()) {
            $minutes = ceil(($lockUntil - time()) / 60);
            $this->session?->flash('error', "Слишком много попыток. Вход заблокирован на {$minutes} мин.");
            return $this->redirect('/admin/login');
        }

        $login = trim((string) $request->input('login', ''));
        $password = (string) $request->input('password', '');

        $configLogin = (string) app_config($this->config, 'admin.login', 'admin');
        $configHash = (string) app_config($this->config, 'admin.password_hash', '');

        if ($login === $configLogin && $configHash !== '' && password_verify($password, $configHash)) {
            // Сброс счётчика при успехе
            $this->session?->forget('admin_attempts');
            $this->session?->forget('admin_lock_until');
            $this->auth->loginAdmin();
            return $this->redirect('/admin/invitations');
        }

        // Увеличиваем счётчик неудачных попыток
        $attempts = (int) ($this->session?->get('admin_attempts', 0) ?? 0) + 1;
        $this->session?->put('admin_attempts', $attempts);

        if ($attempts >= self::MAX_ATTEMPTS) {
            $this->session?->put('admin_lock_until', time() + self::LOCKOUT_SECONDS);
            $this->session?->put('admin_attempts', 0);
            $this->session?->flash('error', 'Слишком много неудачных попыток. Вход заблокирован на 15 минут.');
        } else {
            $remaining = self::MAX_ATTEMPTS - $attempts;
            $this->session?->flash('error', "Неверный логин или пароль. Осталось попыток: {$remaining}.");
        }

        return $this->redirect('/admin/login');
    }

    public function logout(): Response
    {
        $this->auth->logoutAdmin();
        $this->session?->flash('success', 'Вы вышли из системы.');
        return $this->redirect('/admin/login');
    }
}
