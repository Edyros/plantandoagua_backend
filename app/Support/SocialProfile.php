<?php

namespace App\Support;

class SocialProfile
{
    public static function nullable(?string $value, int $max = 255): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $max);
    }

    public static function website(?string $value): ?string
    {
        $value = self::nullable($value);
        if ($value === null) {
            return null;
        }
        if (! preg_match('#^https?://#i', $value)) {
            $value = 'https://'.$value;
        }

        return $value;
    }
}
