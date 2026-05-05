<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

class Warehouse
{
    private ?int $id;

    private int $tenantId;

    private ?int $orgUnitId;

    private string $name;

    private ?string $code;

    private ?string $imagePath;

    private string $type;

    private ?int $addressId;

    private bool $isActive;

    private bool $isDefault;

    private ?array $metadata;

    private int $rowVersion;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        string $name,
        string $type = 'standard',
        ?int $orgUnitId = null,
        ?string $code = null,
        ?string $imagePath = null,
        ?int $addressId = null,
        bool $isActive = true,
        bool $isDefault = false,
        ?array $metadata = null,
        int $rowVersion = 1,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->assertType($type);

        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->orgUnitId = $orgUnitId;
        $this->name = trim($name);
        $this->code = $code !== null ? trim($code) : null;
        $this->imagePath = $imagePath;
        $this->type = $type;
        $this->addressId = $addressId;
        $this->isActive = $isActive;
        $this->isDefault = $isDefault;
        $this->metadata = $metadata;
        $this->rowVersion = $rowVersion;
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

    public function getOrgUnitId(): ?int
    {
        return $this->orgUnitId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAddressId(): ?int
    {
        return $this->addressId;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getRowVersion(): int
    {
        return $this->rowVersion;
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
        string $name,
        string $type,
        ?int $orgUnitId,
        ?string $code,
        ?string $imagePath,
        ?int $addressId,
        bool $isActive,
        bool $isDefault,
        ?array $metadata,
    ): void {
        $this->assertType($type);

        $this->name = trim($name);
        $this->type = $type;
        $this->orgUnitId = $orgUnitId;
        $this->code = $code !== null ? trim($code) : null;
        $this->imagePath = $imagePath;
        $this->addressId = $addressId;
        $this->isActive = $isActive;
        $this->isDefault = $isDefault;
        $this->metadata = $metadata;
        $this->rowVersion++;
        $this->updatedAt = new \DateTimeImmutable;
    }

    private function assertType(string $type): void
    {
        if (! in_array($type, ['standard', 'virtual', 'transit', 'quarantine'], true)) {
            throw new \InvalidArgumentException('Warehouse type is invalid.');
        }
    }
}
