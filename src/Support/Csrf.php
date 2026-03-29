<?php

declare(strict_types=1);

namespace Rent\Support;

use Rent\Http\Session;

class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public function __construct(private readonly Session $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::SESSION_KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->put(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public function validate(?string $token): bool
    {
        $stored = $this->session->get(self::SESSION_KEY);
        return is_string($stored) && is_string($token) && hash_equals($stored, $token);
    }
}
