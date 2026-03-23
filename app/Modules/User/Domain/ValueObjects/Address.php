<?php

namespace Modules\User\Domain\ValueObjects;

use Modules\Core\Domain\ValueObjects\ValueObject;

class Address extends ValueObject
{
    private ?string $street;
    private ?string $city;
    private ?string $state;
    private ?string $postalCode;
    private ?string $country;

    public function __construct(
        ?string $street = null,
        ?string $city = null,
        ?string $state = null,
        ?string $postalCode = null,
        ?string $country = null
    ) {
        $this->street = $street;
        $this->city = $city;
        $this->state = $state;
        $this->postalCode = $postalCode;
        $this->country = $country;
    }

    public function toArray(): array
    {
        return [
            'street'      => $this->street,
            'city'        => $this->city,
            'state'       => $this->state,
            'postal_code' => $this->postalCode,
            'country'     => $this->country,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new static(
            $data['street'] ?? null,
            $data['city'] ?? null,
            $data['state'] ?? null,
            $data['postal_code'] ?? null,
            $data['country'] ?? null
        );
    }
}
