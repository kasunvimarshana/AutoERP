<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

use Illuminate\Support\Collection;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;

class WarehouseZone
{
    private ?int $id;

    private int $warehouseId;

    private int $tenantId;

    private Name $name;

    private ?Code $code;

    private string $type;

    private ?string $description;

    private ?float $capacity;

    private Metadata $metadata;

    private bool $isActive;

    private ?int $parentZoneId;

    private Collection $children;

    private int $lft;

    private int $rgt;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $warehouseId,
        int $tenantId,
        Name $name,
        string $type,
        ?Code $code = null,
        ?string $description = null,
        ?float $capacity = null,
        ?Metadata $metadata = null,
        bool $isActive = true,
        ?int $parentZoneId = null,
        ?int $id = null,
        int $lft = 0,
        int $rgt = 0,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id           = $id;
        $this->warehouseId  = $warehouseId;
        $this->tenantId     = $tenantId;
        $this->name         = $name;
        $this->type         = $type;
        $this->code         = $code;
        $this->description  = $description;
        $this->capacity     = $capacity;
        $this->metadata     = $metadata ?? new Metadata([]);
        $this->isActive     = $isActive;
        $this->parentZoneId = $parentZoneId;
        $this->children     = new Collection;
        $this->lft          = $lft;
        $this->rgt          = $rgt;
        $this->createdAt    = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt    = $updatedAt ?? new \DateTimeImmutable;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWarehouseId(): int
    {
        return $this->warehouseId;
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

    public function getCapacity(): ?float
    {
        return $this->capacity;
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getParentZoneId(): ?int
    {
        return $this->parentZoneId;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function getLft(): int
    {
        return $this->lft;
    }

    public function getRgt(): int
    {
        return $this->rgt;
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
        ?float $capacity,
        ?Metadata $metadata,
        bool $isActive
    ): void {
        $this->name        = $name;
        $this->type        = $type;
        $this->code        = $code;
        $this->description = $description;
        $this->capacity    = $capacity;
        $this->metadata    = $metadata ?? new Metadata([]);
        $this->isActive    = $isActive;
        $this->updatedAt   = new \DateTimeImmutable;
    }

    public function setParentZoneId(?int $parentZoneId): void
    {
        $this->parentZoneId = $parentZoneId;
        $this->updatedAt    = new \DateTimeImmutable;
    }

    public function setLftRgt(int $lft, int $rgt): void
    {
        $this->lft       = $lft;
        $this->rgt       = $rgt;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function addChild(WarehouseZone $child): void
    {
        if ($this->children->contains(fn ($c) => $c->getId() === $child->getId())) {
            return;
        }
        $this->children->add($child);
        $child->setParentZoneId($this->id);
    }

    public function removeChild(WarehouseZone $child): void
    {
        $this->children = $this->children->reject(fn ($c) => $c->getId() === $child->getId());
        $child->setParentZoneId(null);
    }
}
