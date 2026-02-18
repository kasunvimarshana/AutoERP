<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Phone Number Value Object
 * 
 * Immutable representation of a phone number
 */
final class PhoneNumber implements JsonSerializable, Stringable
{
    private function __construct(
        private readonly string $number,
        private readonly string $countryCode
    ) {
        // Remove all non-numeric characters for validation
        $cleaned = preg_replace('/[^0-9]/', '', $number);
        
        if (empty($cleaned) || strlen($cleaned) < 7 || strlen($cleaned) > 15) {
            throw new InvalidArgumentException("Invalid phone number: {$number}");
        }
        
        if (!preg_match('/^\+?[1-9]\d{0,3}$/', $countryCode)) {
            throw new InvalidArgumentException("Invalid country code: {$countryCode}");
        }
    }

    public static function fromString(string $number, string $countryCode = '+1'): self
    {
        return new self(trim($number), $countryCode);
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getFullNumber(): string
    {
        return $this->countryCode . $this->number;
    }

    public function format(): string
    {
        // Simple formatting - can be enhanced with libphonenumber
        $cleaned = preg_replace('/[^0-9]/', '', $this->number);
        
        if (strlen($cleaned) === 10) {
            // US format: (XXX) XXX-XXXX
            return sprintf(
                '%s (%s) %s-%s',
                $this->countryCode,
                substr($cleaned, 0, 3),
                substr($cleaned, 3, 3),
                substr($cleaned, 6)
            );
        }
        
        return $this->countryCode . ' ' . $cleaned;
    }

    public function equals(PhoneNumber $other): bool
    {
        return $this->getFullNumber() === $other->getFullNumber();
    }

    public function jsonSerialize(): array
    {
        return [
            'number' => $this->number,
            'country_code' => $this->countryCode,
            'full_number' => $this->getFullNumber(),
            'formatted' => $this->format(),
        ];
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
