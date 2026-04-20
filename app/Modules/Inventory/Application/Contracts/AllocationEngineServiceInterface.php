<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Application\DTOs\AllocationRequestDTO;
use Modules\Inventory\Domain\Entities\AllocationResult;

/**
 * Orchestrates stock allocation: selects cost layers according to the
 * configured allocation strategy and returns an AllocationResult.
 */
interface AllocationEngineServiceInterface
{
    /**
     * Allocate stock for the given request and return the result.
     */
    public function allocate(AllocationRequestDTO $request): AllocationResult;

    /**
     * Resolve the effective allocation strategy for a given context.
     */
    public function resolveAllocationStrategy(
        int $tenantId,
        ?int $productId = null,
        ?int $warehouseId = null,
        ?int $orgUnitId = null,
        ?string $transactionType = null,
    ): string;
}
