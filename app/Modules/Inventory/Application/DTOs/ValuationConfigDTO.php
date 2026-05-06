<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

/**
 * Input for a valuation-config create/update operation.
 */
readonly class ValuationConfigDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly ?int $orgUnitId,
        public readonly ?int $warehouseId,
        public readonly ?int $productId,
        public readonly ?string $transactionType,
        public readonly string $valuationMethod,
        public readonly string $allocationStrategy,
        public readonly bool $isActive,
        public readonly ?array $metadata,
    ) {}
}
