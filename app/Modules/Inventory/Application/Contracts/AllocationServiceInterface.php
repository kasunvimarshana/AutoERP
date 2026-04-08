<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Core\Application\Contracts\ServiceInterface;

interface AllocationServiceInterface extends ServiceInterface
{
    /**
     * Allocate batch/lots for the given product and warehouse using the configured strategy.
     *
     * @param  string  $strategy  'fifo' | 'fefo' | 'lifo'
     * @return Collection<int, array{batch_lot_id: string, quantity: float}>
     */
    public function allocate(
        string $productId,
        string $warehouseId,
        float $quantity,
        string $strategy,
        ?string $variantId = null,
    ): Collection;
}
