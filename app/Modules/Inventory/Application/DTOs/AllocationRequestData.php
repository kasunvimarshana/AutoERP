<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

/**
 * Input for an allocation engine request.
 */
readonly class AllocationRequestData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly ?int $variantId,
        public readonly int $locationId,
        public readonly string $requiredQuantity,
        public readonly string $allocationStrategy,
        /** @var array<string, mixed> */
        public readonly array $context = [],
    ) {}
}
