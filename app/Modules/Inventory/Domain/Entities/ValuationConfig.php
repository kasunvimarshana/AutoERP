<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Entities;

/**
 * Configures the valuation method and allocation strategy for a given scope.
 *
 * Scopes are resolved in priority order:
 *   product > warehouse > org_unit > tenant
 *
 * A null scope key means "not restricted to that scope level".
 */
class ValuationConfig
{
    private ?int $id;

    public function __construct(
        private readonly int $tenantId,
        private readonly ?int $orgUnitId,
        private readonly ?int $warehouseId,
        private readonly ?int $productId,
        private readonly ?string $transactionType,
        private readonly string $valuationMethod,
        private readonly string $allocationStrategy,
        private readonly bool $isActive,
        private readonly ?array $metadata,
        ?int $id = null,
    ) {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getOrgUnitId(): ?int
    {
        return $this->orgUnitId;
    }

    public function getWarehouseId(): ?int
    {
        return $this->warehouseId;
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function getTransactionType(): ?string
    {
        return $this->transactionType;
    }

    public function getValuationMethod(): string
    {
        return $this->valuationMethod;
    }

    public function getAllocationStrategy(): string
    {
        return $this->allocationStrategy;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }
}
