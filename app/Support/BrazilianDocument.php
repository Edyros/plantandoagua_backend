<?php

namespace App\Support;

class BrazilianDocument
{
    public static function digits(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?: '';
    }

    public static function isValid(?string $value): bool
    {
        $digits = self::digits($value);
        if (strlen($digits) === 11) {
            return self::isValidCpf($digits);
        }
        if (strlen($digits) === 14) {
            return self::isValidCnpj($digits);
        }

        return false;
    }

    public static function isValidCpf(string $digits): bool
    {
        if (strlen($digits) !== 11 || preg_match('/^(\d)\1{10}$/', $digits)) {
            return false;
        }

        for ($position = 9; $position < 11; $position++) {
            $sum = 0;
            for ($i = 0; $i < $position; $i++) {
                $sum += (int) $digits[$i] * (($position + 1) - $i);
            }
            $check = ((10 * $sum) % 11) % 10;
            if ((int) $digits[$position] !== $check) {
                return false;
            }
        }

        return true;
    }

    public static function isValidCnpj(string $digits): bool
    {
        if (strlen($digits) !== 14 || preg_match('/^(\d)\1{13}$/', $digits)) {
            return false;
        }

        $weights = [
            [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
            [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
        ];

        for ($checkIndex = 0; $checkIndex < 2; $checkIndex++) {
            $size = 12 + $checkIndex;
            $sum = 0;
            foreach ($weights[$checkIndex] as $i => $weight) {
                $sum += (int) $digits[$i] * $weight;
            }
            $check = $sum % 11;
            $check = $check < 2 ? 0 : 11 - $check;
            if ((int) $digits[$size] !== $check) {
                return false;
            }
        }

        return true;
    }
}
