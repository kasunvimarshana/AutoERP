<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Core\Application\Contracts\ServiceInterface;

interface CycleCountServiceInterface extends ServiceInterface
{
    /**
     * Create a new cycle count with its lines.
     */
    public function createCycleCount(array $data): mixed;

    /**
     * Submit counted quantities for a cycle count, computing variances.
     */
    public function submitCount(string $cycleCountId, array $lines): mixed;

    /**
     * Approve a cycle count and post inventory adjustments for variances.
     */
    public function approve(string $cycleCountId): mixed;

    /**
     * Cancel a draft cycle count.
     */
    public function cancel(string $cycleCountId): mixed;
}
