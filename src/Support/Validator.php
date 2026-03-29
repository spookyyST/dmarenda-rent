<?php

declare(strict_types=1);

namespace Rent\Support;

class Validator
{
    public static function email(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function phone(string $value): bool
    {
        return (bool) preg_match('/^[0-9+\-()\s]{6,30}$/', $value);
    }

    public static function date(string $value): bool
    {
        $dt = date_create($value);
        return $dt !== false && $dt->format('Y-m-d') === $value;
    }

    public static function positiveAmount(string $value): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        return (float) $value > 0;
    }

    public static function required(string $value): bool
    {
        return trim($value) !== '';
    }
}
