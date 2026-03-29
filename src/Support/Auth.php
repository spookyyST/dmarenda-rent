<?php

declare(strict_types=1);

namespace Rent\Support;

use Rent\Http\Session;

class Auth
{
    private const ADMIN_KEY = 'admin_logged_in';
    private const TENANT_KEY = 'tenant_token';

    public function __construct(private readonly Session $session)
    {
    }

    public function loginAdmin(): void
    {
        $this->session->put(self::ADMIN_KEY, true);
    }

    public function logoutAdmin(): void
    {
        $this->session->forget(self::ADMIN_KEY);
    }

    public function isAdmin(): bool
    {
        return $this->session->get(self::ADMIN_KEY, false) === true;
    }

    public function loginTenant(string $token): void
    {
        $this->session->put(self::TENANT_KEY, $token);
    }

    public function logoutTenant(): void
    {
        $this->session->forget(self::TENANT_KEY);
    }

    public function isTenant(string $token): bool
    {
        return $this->session->get(self::TENANT_KEY) === $token;
    }
}
