<?php

declare(strict_types=1);

namespace Rent\Http;

class Session
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            session_start([
                'cookie_httponly' => true,
                'cookie_secure' => $isHttps,
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => true,
            ]);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        if (!isset($_SESSION['_flash'][$key])) {
            return $default;
        }

        $value = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);

        return $value;
    }

    public function invalidate(): void
    {
        $_SESSION = [];
        if (session_id() !== '') {
            session_destroy();
        }
    }
}
