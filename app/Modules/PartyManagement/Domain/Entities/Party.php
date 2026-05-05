<?php

declare(strict_types=1);

namespace Modules\PartyManagement\Domain\Entities;

class Party
{
    public function __construct(
        private readonly string $id,
        private readonly int $tenantId,
        private readonly string $partyType,
        private string $name,
        private ?string $taxNumber,
        private ?string $email,
        private ?string $phone,
        private ?string $addressLine1,
        private ?string $addressLine2,
        private ?string $city,
        private ?string $stateProvince,
        private ?string $postalCode,
        private ?string $countryCode,
        private bool $isActive,
        private ?string $notes,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getPartyType(): string
    {
        return $this->partyType;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTaxNumber(): ?string
    {
        return $this->taxNumber;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getAddressLine1(): ?string
    {
        return $this->addressLine1;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getStateProvince(): ?string
    {
        return $this->stateProvince;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function update(
        string $name,
        ?string $taxNumber,
        ?string $email,
        ?string $phone,
        ?string $addressLine1,
        ?string $addressLine2,
        ?string $city,
        ?string $stateProvince,
        ?string $postalCode,
        ?string $countryCode,
        bool $isActive,
        ?string $notes,
    ): void {
        $this->name = $name;
        $this->taxNumber = $taxNumber;
        $this->email = $email;
        $this->phone = $phone;
        $this->addressLine1 = $addressLine1;
        $this->addressLine2 = $addressLine2;
        $this->city = $city;
        $this->stateProvince = $stateProvince;
        $this->postalCode = $postalCode;
        $this->countryCode = $countryCode;
        $this->isActive = $isActive;
        $this->notes = $notes;
    }
}
