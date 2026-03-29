<?php

declare(strict_types=1);

namespace Rent\Http;

class Request
{
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $post,
        private readonly array $files,
        private readonly array $server,
        private readonly string $rawBody
    ) {
    }

    public static function capture(string $basePath = ''): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($requestUri, PHP_URL_PATH) ?: '/';

        if ($basePath !== '' && $basePath !== '/' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
            $path = $path === '' ? '/' : $path;
        }

        if (str_starts_with($path, '/index.php')) {
            $path = substr($path, strlen('/index.php'));
            $path = $path === '' ? '/' : $path;
        }

        return new self(
            $method,
            $path,
            $_GET,
            $_POST,
            $_FILES,
            $_SERVER,
            file_get_contents('php://input') ?: ''
        );
    }

    public static function fromArray(array $server, string $rawBody = ''): self
    {
        return new self(
            strtoupper($server['REQUEST_METHOD'] ?? 'GET'),
            $server['REQUEST_PATH'] ?? '/',
            [],
            [],
            [],
            $server,
            $rawBody
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->post;
    }

    public function files(): array
    {
        return $this->files;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function rawBody(): string
    {
        return $this->rawBody;
    }

    public function json(): ?array
    {
        if ($this->rawBody === '') {
            return null;
        }

        $decoded = json_decode($this->rawBody, true);
        return is_array($decoded) ? $decoded : null;
    }

    public function ip(): string
    {
        $ip = $this->server('HTTP_X_FORWARDED_FOR');
        if (is_string($ip) && $ip !== '') {
            $parts = explode(',', $ip);
            return trim($parts[0]);
        }

        return (string) ($this->server('REMOTE_ADDR', '0.0.0.0'));
    }
}
