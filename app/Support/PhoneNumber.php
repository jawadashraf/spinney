<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Normalises phone numbers to E.164 so numbers stored as 07…, +44 7…, 0044 7… or
 * with spaces and brackets all match the same caller. National numbers are assumed to be UK.
 */
final class PhoneNumber
{
    public const string DEFAULT_COUNTRY_CODE = '44';

    public static function normalize(?string $number): ?string
    {
        if ($number === null) {
            return null;
        }

        $hasPlus = str_starts_with(mb_ltrim($number), '+');
        $digits = preg_replace('/\D/', '', $number) ?? '';

        if ($digits === '') {
            return null;
        }

        if (! $hasPlus && str_starts_with($digits, '00')) {
            $digits = mb_substr($digits, 2);
            $hasPlus = true;
        }

        if (! $hasPlus && str_starts_with($digits, self::DEFAULT_COUNTRY_CODE) && mb_strlen($digits) >= 11) {
            $hasPlus = true;
        }

        if ($hasPlus) {
            // Drop the trunk prefix written as "+44 (0) 7…".
            if (str_starts_with($digits, self::DEFAULT_COUNTRY_CODE.'0')) {
                $digits = self::DEFAULT_COUNTRY_CODE.mb_substr($digits, 3);
            }

            return '+'.$digits;
        }

        if (str_starts_with($digits, '0')) {
            return '+'.self::DEFAULT_COUNTRY_CODE.mb_substr($digits, 1);
        }

        // A UK number whose leading 0 was dropped, e.g. 7438356414.
        if (mb_strlen($digits) === 10) {
            return '+'.self::DEFAULT_COUNTRY_CODE.$digits;
        }

        // Extensions and other short codes are left as plain digits so they never match a contact.
        return $digits;
    }

    /**
     * Digits to look for inside stored E.164 numbers when someone types part of a number.
     */
    public static function searchFragment(string $input): ?string
    {
        $digits = preg_replace('/\D/', '', $input) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = mb_ltrim($digits, '0');
        }

        return mb_strlen($digits) >= 3 ? $digits : null;
    }
}
