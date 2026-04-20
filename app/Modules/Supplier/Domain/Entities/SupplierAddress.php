<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\Entities;

class SupplierAddress
{
    private ?int $id;

    private int $tenantId;

    private int $supplierId;

    private string $type;

    private ?string $label;

    private string $addressLine1;

    private ?string $addressLine2;

    private string $city;

    private ?string $state;

    private string $postalCode;

    private int $countryId;

    private bool $isDefault;

    private ?string $geoLat;

    private ?string $geoLng;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $supplierId,
        string $type,
        string $addressLine1,
        string $city,
        string $postalCode,
        int $countryId,
        ?string $label = null,
        ?string $addressLine2 = null,
        ?string $state = null,
        bool $isDefault = false,
        ?string $geoLat = null,
        ?string $geoLng = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->assertType($type);

        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->supplierId = $supplierId;
        $this->type = $type;
        $this->label = $label;
        $this->addressLine1 = $addressLine1;
        $this->addressLine2 = $addressLine2;
        $this->city = $city;
        $this->state = $state;
        $this->postalCode = $postalCode;
        $this->countryId = $countryId;
        $this->isDefault = $isDefault;
        $this->geoLat = $geoLat;
        $this->geoLng = $geoLng;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getSupplierId(): int
    {
        return $this->supplierId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getAddressLine1(): string
    {
        return $this->addressLine1;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCountryId(): int
    {
        return $this->countryId;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function getGeoLat(): ?string
    {
        return $this->geoLat;
    }

    public function getGeoLng(): ?string
    {
        return $this->geoLng;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function update(
        string $type,
        string $addressLine1,
        string $city,
        string $postalCode,
        int $countryId,
        ?string $label,
        ?string $addressLine2,
        ?string $state,
        bool $isDefault,
        ?string $geoLat,
        ?string $geoLng,
    ): void {
        $this->assertType($type);

        $this->type = $type;
        $this->label = $label;
        $this->addressLine1 = $addressLine1;
        $this->addressLine2 = $addressLine2;
        $this->city = $city;
        $this->state = $state;
        $this->postalCode = $postalCode;
        $this->countryId = $countryId;
        $this->isDefault = $isDefault;
        $this->geoLat = $geoLat;
        $this->geoLng = $geoLng;
        $this->updatedAt = new \DateTimeImmutable;
    }

    private function assertType(string $type): void
    {
        if (! in_array($type, ['billing', 'shipping', 'other'], true)) {
            throw new \InvalidArgumentException('Address type must be billing, shipping, or other.');
        }
    }
}
