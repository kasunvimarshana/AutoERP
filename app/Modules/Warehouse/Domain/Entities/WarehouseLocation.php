<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Entities;

class WarehouseLocation
{
    private ?int $id;

    private int $tenantId;

    private int $warehouseId;

    private ?int $orgUnitId;

    private ?int $parentId;

    private string $name;

    private ?string $code;

    private ?string $path;

    private int $depth;

    private string $type;

    private bool $isActive;

    private bool $isPickable;

    private bool $isReceivable;

    private ?string $capacity;

    private ?array $metadata;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $warehouseId,
        string $name,
        string $type = 'bin',
        ?int $orgUnitId = null,
        ?int $parentId = null,
        ?string $code = null,
        ?string $path = null,
        int $depth = 0,
        bool $isActive = true,
        bool $isPickable = true,
        bool $isReceivable = true,
        ?string $capacity = null,
        ?array $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
    ) {
        $this->assertType($type);

        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->warehouseId = $warehouseId;
        $this->orgUnitId = $orgUnitId;
        $this->parentId = $parentId;
        $this->name = trim($name);
        $this->code = $code !== null ? trim($code) : null;
        $this->path = $path;
        $this->depth = max(0, $depth);
        $this->type = $type;
        $this->isActive = $isActive;
        $this->isPickable = $isPickable;
        $this->isReceivable = $isReceivable;
        $this->capacity = $capacity;
        $this->metadata = $metadata;
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

    public function getWarehouseId(): int
    {
        return $this->warehouseId;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
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

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isPickable(): bool
    {
        return $this->isPickable;
    }

    public function isReceivable(): bool
    {
        return $this->isReceivable;
    }

    public function getCapacity(): ?string
    {
        return $this->capacity;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
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
        ?int $parentId,
        ?string $code,
        ?string $path,
        int $depth,
        bool $isActive,
        bool $isPickable,
        bool $isReceivable,
        ?string $capacity,
        ?array $metadata,
    ): void {
        $this->assertType($type);

        $this->name = trim($name);
        $this->type = $type;
        $this->orgUnitId = $orgUnitId;
        $this->parentId = $parentId;
        $this->code = $code !== null ? trim($code) : null;
        $this->path = $path;
        $this->depth = max(0, $depth);
        $this->isActive = $isActive;
        $this->isPickable = $isPickable;
        $this->isReceivable = $isReceivable;
        $this->capacity = $capacity;
        $this->metadata = $metadata;
        $this->updatedAt = new \DateTimeImmutable;
    }

    private function assertType(string $type): void
    {
        if (! in_array($type, ['zone', 'aisle', 'rack', 'shelf', 'bin', 'staging', 'dispatch'], true)) {
            throw new \InvalidArgumentException('Warehouse location type is invalid.');
        }
    }
}
