<?php

namespace App\Support;

use InvalidArgumentException;

class Money
{
    public static function of(int|float|string $amount): string
    {
        if (! is_numeric($amount)) {
            throw new InvalidArgumentException('Invalid money amount.');
        }

        return number_format((float) $amount, 2, '.', '');
    }

    public static function add(int|float|string ...$amounts): string
    {
        $total = '0.00';

        foreach ($amounts as $amount) {
            $total = self::bc('bcadd', $total, self::of($amount));
        }

        return $total;
    }

    public static function subtract(int|float|string $left, int|float|string $right): string
    {
        return self::bc('bcsub', self::of($left), self::of($right));
    }

    public static function multiply(int|float|string $amount, int|float|string $multiplier): string
    {
        return self::bc('bcmul', self::of($amount), self::of($multiplier));
    }

    public static function percent(int|float|string $amount, int|float|string $percent): string
    {
        return self::multiply($amount, self::of(((float) $percent) / 100));
    }

    public static function min(int|float|string $left, int|float|string $right): string
    {
        return self::compare($left, $right) <= 0 ? self::of($left) : self::of($right);
    }

    public static function max(int|float|string $left, int|float|string $right): string
    {
        return self::compare($left, $right) >= 0 ? self::of($left) : self::of($right);
    }

    public static function compare(int|float|string $left, int|float|string $right): int
    {
        if (function_exists('bccomp')) {
            return bccomp(self::of($left), self::of($right), 2);
        }

        return (float) self::of($left) <=> (float) self::of($right);
    }

    public static function greaterOrEqual(int|float|string $left, int|float|string $right): bool
    {
        return self::compare($left, $right) >= 0;
    }

    /**
     * @param  callable(string, string, int): string  $callback
     */
    private static function bc(string $function, string $left, string $right): string
    {
        if (function_exists($function)) {
            return $function($left, $right, 2);
        }

        $result = match ($function) {
            'bcadd' => (float) $left + (float) $right,
            'bcsub' => (float) $left - (float) $right,
            'bcmul' => (float) $left * (float) $right,
            default => throw new InvalidArgumentException('Unsupported money operation.'),
        };

        return self::of($result);
    }
}
