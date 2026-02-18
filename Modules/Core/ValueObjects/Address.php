<?php

declare(strict_types=1);

namespace Modules\Core\ValueObjects;

use Modules\Core\Exceptions\ValidationException;

/**
 * Address Value Object
 *
 * Represents a physical address
 * Immutable - once created, cannot be changed
 */
final class Address
{
    private readonly string $line1;

    private readonly ?string $line2;

    private readonly string $city;

    private readonly ?string $state;

    private readonly string $postalCode;

    private readonly string $country; // ISO 3166-1 alpha-2 code

    /**
     * Create a new address value object
     *
     * @param  string  $country  ISO 3166-1 alpha-2 country code (e.g., US, GB)
     *
     * @throws ValidationException
     */
    public function __construct(
        string $line1,
        string $city,
        string $postalCode,
        string $country,
        ?string $line2 = null,
        ?string $state = null
    ) {
        if (empty(trim($line1))) {
            throw ValidationException::forField('address.line1', 'Address line 1 is required');
        }

        if (empty(trim($city))) {
            throw ValidationException::forField('address.city', 'City is required');
        }

        if (empty(trim($postalCode))) {
            throw ValidationException::forField('address.postal_code', 'Postal code is required');
        }

        $country = strtoupper($country);
        if (strlen($country) !== 2) {
            throw ValidationException::forField(
                'address.country',
                'Country must be a 2-letter ISO code'
            );
        }

        $this->line1 = trim($line1);
        $this->line2 = $line2 ? trim($line2) : null;
        $this->city = trim($city);
        $this->state = $state ? trim($state) : null;
        $this->postalCode = trim($postalCode);
        $this->country = $country;
    }

    /**
     * Get address line 1
     */
    public function getLine1(): string
    {
        return $this->line1;
    }

    /**
     * Get address line 2
     */
    public function getLine2(): ?string
    {
        return $this->line2;
    }

    /**
     * Get city
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * Get state/province
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * Get postal code
     */
    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    /**
     * Get country code
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * Get full address as single string
     */
    public function getFullAddress(): string
    {
        $parts = [
            $this->line1,
            $this->line2,
            $this->city,
            $this->state,
            $this->postalCode,
            $this->country,
        ];

        return implode(', ', array_filter($parts));
    }

    /**
     * Check if address equals another
     */
    public function equals(Address $other): bool
    {
        return $this->line1 === $other->line1
            && $this->line2 === $other->line2
            && $this->city === $other->city
            && $this->state === $other->state
            && $this->postalCode === $other->postalCode
            && $this->country === $other->country;
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return $this->getFullAddress();
    }

    /**
     * Create from array
     *
     * @param  array<string, string|null>  $data
     *
     * @throws ValidationException
     */
    public static function fromArray(array $data): static
    {
        return new self(
            $data['line1'] ?? '',
            $data['city'] ?? '',
            $data['postal_code'] ?? '',
            $data['country'] ?? '',
            $data['line2'] ?? null,
            $data['state'] ?? null
        );
    }

    /**
     * Serialize to array
     *
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'line1' => $this->line1,
            'line2' => $this->line2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
            'full_address' => $this->getFullAddress(),
        ];
    }
}
