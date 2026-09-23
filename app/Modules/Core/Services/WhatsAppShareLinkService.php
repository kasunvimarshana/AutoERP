<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use InvalidArgumentException;

final class WhatsAppShareLinkService
{
    private const MINIMUM_E164_DIGITS = 8;

    private const MAXIMUM_E164_DIGITS = 15;

    /** @return array{phone:string, whatsapp_url:string} */
    public function create(string $phone, string $message): array
    {
        $normalizedPhone = $this->normalize($phone);

        return [
            'phone' => $normalizedPhone,
            'whatsapp_url' => 'https://wa.me/'.$normalizedPhone.'?text='.rawurlencode($message),
        ];
    }

    public function normalize(string $phone): string
    {
        $value = trim($phone);
        if ($value === '') {
            throw new InvalidArgumentException('The selected recipient does not have a WhatsApp number.');
        }

        $isInternational = str_starts_with($value, '+') || str_starts_with($value, '00');
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (! $isInternational) {
            $callingCode = $this->callingCode();
            $nationalNumber = ltrim($digits, '0');
            $digits = str_starts_with($nationalNumber, $callingCode)
                ? $nationalNumber
                : $callingCode.$nationalNumber;
        }

        $length = strlen($digits);
        if ($digits === ''
            || str_starts_with($digits, '0')
            || $length < self::MINIMUM_E164_DIGITS
            || $length > self::MAXIMUM_E164_DIGITS) {
            throw new InvalidArgumentException('The selected recipient has an invalid WhatsApp number.');
        }

        return $digits;
    }

    private function callingCode(): string
    {
        $configured = (string) config('document-sharing.whatsapp.default_country_calling_code', '');
        $digits = preg_replace('/\D+/', '', $configured) ?? '';
        if ($digits === '' || str_starts_with($digits, '0')) {
            throw new InvalidArgumentException('The default document-sharing country calling code is invalid.');
        }

        return $digits;
    }
}
