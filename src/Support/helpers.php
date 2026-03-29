<?php

declare(strict_types=1);

use Rent\Http\Response;

if (!function_exists('app_config')) {
    function app_config(array $config, string $path, mixed $default = null): mixed
    {
        $segments = explode('.', $path);
        $value = $config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('app_now')) {
    function app_now(string $timezone): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone($timezone));
    }
}

if (!function_exists('format_money')) {
    function format_money(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url): Response
    {
        return Response::redirect($url);
    }
}
