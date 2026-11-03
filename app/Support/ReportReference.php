<?php

namespace App\Support;

use App\Models\Report;

class ReportReference
{
    /**
     * Crockford base32: digits plus letters, excluding I, L, O and U so a
     * reference can be read over the phone without ambiguity.
     */
    public const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    public static function generate(): string
    {
        do {
            $suffix = '';

            for ($i = 0; $i < 6; $i++) {
                $suffix .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }

            $reference = 'NCC-'.now()->year.'-'.$suffix;
        } while (Report::withTrashed()->where('reference_number', $reference)->exists());

        return $reference;
    }

    public static function pin(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public static function normalize(?string $reference): string
    {
        return preg_replace('/\s+/', '', strtoupper(trim((string) $reference))) ?? '';
    }
}
