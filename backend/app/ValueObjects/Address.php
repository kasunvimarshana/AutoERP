<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Address Value Object
 * 
 * Immutable representation of a physical address
 */
final class Address implements JsonSerializable, Stringable
{
    private function __construct(
        private readonly string $street,
        private readonly string $city,
        private readonly string $state,
        private readonly string $postalCode,
        private readonly string $country
    ) {
        if (empty($street) || empty($city) || empty($country)) {
            throw new InvalidArgumentException("Street, city, and country are required");
        }
    }

    public static function create(
        string $street,
        string $city,
        string $state,
        string $postalCode,
        string $country
    ): self {
        return new self(
            trim($street),
            trim($city),
            trim($state),
            trim($postalCode),
            trim($country)
        );
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function format(): string
    {
        $parts = array_filter([
            $this->street,
            $this->city,
            $this->state,
            $this->postalCode,
            $this->country,
        ]);
        
        return implode(', ', $parts);
    }

    public function equals(Address $other): bool
    {
        return $this->street === $other->street
            && $this->city === $other->city
            && $this->state === $other->state
            && $this->postalCode === $other->postalCode
            && $this->country === $other->country;
    }

    public function jsonSerialize(): array
    {
        return [
            'street' => $this->street,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
            'formatted' => $this->format(),
        ];
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
