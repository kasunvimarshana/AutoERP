<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;

class Warehouse
{
    private ?int $id;

    private int $tenantId;

    private Name $name;

    private ?Code $code;

    private string $type;

    private ?string $description;

    private ?string $address;

    private ?float $capacity;

    private ?int $locationId;

    private Metadata $metadata;

    private bool $isActive;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        Name $name,
        string $type,
        ?Code $code = null,
        ?string $description = null,
        ?string $address = null,
        ?float $capacity = null,
        ?int $locationId = null,
        ?Metadata $metadata = null,
        bool $isActive = true,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id          = $id;
        $this->tenantId    = $tenantId;
        $this->name        = $name;
        $this->type        = $type;
        $this->code        = $code;
        $this->description = $description;
        $this->address     = $address;
        $this->capacity    = $capacity;
        $this->locationId  = $locationId;
        $this->metadata    = $metadata ?? new Metadata([]);
        $this->isActive    = $isActive;
        $this->createdAt   = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt   = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function getCode(): ?Code
    {
        return $this->code;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getCapacity(): ?float
    {
        return $this->capacity;
    }

    public function getLocationId(): ?int
    {
        return $this->locationId;
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function updateDetails(
        Name $name,
        string $type,
        ?Code $code,
        ?string $description,
        ?string $address,
        ?float $capacity,
        ?int $locationId,
        ?Metadata $metadata,
        bool $isActive
    ): void {
        $this->name        = $name;
        $this->type        = $type;
        $this->code        = $code;
        $this->description = $description;
        $this->address     = $address;
        $this->capacity    = $capacity;
        $this->locationId  = $locationId;
        $this->metadata    = $metadata ?? new Metadata([]);
        $this->isActive    = $isActive;
        $this->updatedAt   = new \DateTimeImmutable;
    }

    public function activate(): void
    {
        $this->isActive  = true;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function deactivate(): void
    {
        $this->isActive  = false;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
