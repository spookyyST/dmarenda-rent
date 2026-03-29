<?php

declare(strict_types=1);

namespace Rent\Controller;

use Rent\Http\Response;
use Rent\Http\Session;
use Rent\Support\Csrf;
use Rent\Support\View;

abstract class BaseController
{
    public function __construct(
        protected readonly array $config,
        protected readonly View $view,
        protected readonly ?Session $session = null,
        protected readonly ?Csrf $csrf = null
    ) {
    }

    protected function render(string $template, array $data = [], string $title = ''): Response
    {
        $common = [
            'app' => $this->config['app'],
            'base_path' => app_config($this->config, 'app.base_path', '/rent'),
            'csrf_token' => $this->csrf?->token(),
            'flash_success' => $this->session?->getFlash('success'),
            'flash_error' => $this->session?->getFlash('error'),
        ];

        $content = $this->view->render($template, array_merge($common, $data));
        $html = $this->view->render('partials/layout.php', array_merge($common, [
            'title' => $title !== '' ? $title : app_config($this->config, 'app.name', 'ДМаренда'),
            'content' => $content,
        ]));

        return Response::html($html);
    }

    protected function redirect(string $path): Response
    {
        $basePath = rtrim((string) app_config($this->config, 'app.base_path', '/rent'), '/');
        return Response::redirect($basePath . $path);
    }

    protected function assetUrl(string $relative): string
    {
        $basePath = rtrim((string) app_config($this->config, 'app.base_path', '/rent'), '/');
        return $basePath . $relative;
    }

    protected function requireCsrf(string $token): void
    {
        if ($this->csrf === null || !$this->csrf->validate($token)) {
            throw new \RuntimeException('CSRF token mismatch.');
        }
    }
}
