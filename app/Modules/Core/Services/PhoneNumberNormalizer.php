<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use InvalidArgumentException;

class PhoneNumberNormalizer
{
    private const MinimumDigits = 8;

    private const MaximumDigits = 15;

    public function normalize(string $phone): string
    {
        $value = trim($phone);
        if ($value === '' || ! preg_match('/^[0-9+().\s-]+$/', $value)) {
            throw new InvalidArgumentException('Enter a valid phone number, including its area or country code.');
        }

        $isInternational = str_starts_with($value, '+') || str_starts_with($value, '00');
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (! $isInternational) {
            // Use the same configured country code as document sharing and verification.
            $callingCode = preg_replace('/\D+/', '', (string) config('document-sharing.whatsapp.default_country_calling_code', '')) ?? '';
            if ($callingCode === '' || str_starts_with($callingCode, '0')) {
                throw new InvalidArgumentException('The default country calling code is invalid.');
            }
            $nationalNumber = ltrim($digits, '0');
            $digits = str_starts_with($nationalNumber, $callingCode) ? $nationalNumber : $callingCode.$nationalNumber;
        }

        if (str_starts_with($digits, '0') || strlen($digits) < self::MinimumDigits || strlen($digits) > self::MaximumDigits) {
            throw new InvalidArgumentException('Enter a valid phone number, including its area or country code.');
        }

        return $digits;
    }
}
