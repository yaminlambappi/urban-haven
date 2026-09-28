<?php

namespace App\Support;

use InvalidArgumentException;

final class MoneyFormatter
{
    public static function formatBdt(string|int|float|null $amount, string $basis = 'total_sale'): string
    {
        if ($amount === null || $amount === '') {
            return 'Price on request';
        }

        $numeric = self::toNumericString($amount);
        $formatted = number_format((float) $numeric, 0, '.', ',');
        $suffix = $basis === 'monthly_rent' ? '/month' : '';

        return 'BDT '.$formatted.$suffix;
    }

    public static function toNumericString(string|int|float $amount): string
    {
        if (is_float($amount) && (is_nan($amount) || is_infinite($amount))) {
            throw new InvalidArgumentException('Money amount must be a finite number.');
        }

        return is_string($amount) ? $amount : (string) $amount;
    }
}
