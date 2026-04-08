<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Collection;
use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\AllocationServiceInterface;
use Modules\Inventory\Application\Services\Allocation\FefoAllocationStrategy;
use Modules\Inventory\Application\Services\Allocation\FifoAllocationStrategy;
use Modules\Inventory\Application\Services\Allocation\LifoAllocationStrategy;
use Modules\Inventory\Domain\Contracts\AllocationStrategyInterface;
use Modules\Inventory\Domain\Contracts\Repositories\BatchLotRepositoryInterface;
use Modules\Inventory\Domain\Exceptions\InsufficientStockException;

class AllocationService extends BaseService implements AllocationServiceInterface
{
    /** @var array<string, AllocationStrategyInterface> */
    private array $strategies;

    public function __construct(
        BatchLotRepositoryInterface $repository,
        FifoAllocationStrategy $fifo,
        FefoAllocationStrategy $fefo,
        LifoAllocationStrategy $lifo,
    ) {
        parent::__construct($repository);
        $this->strategies = [
            'fifo' => $fifo,
            'fefo' => $fefo,
            'lifo' => $lifo,
        ];
    }

    /**
     * Default execute handler.
     */
    protected function handle(array $data): mixed
    {
        return $this->allocate(
            $data['product_id'],
            $data['warehouse_id'],
            (float) $data['quantity'],
            $data['strategy'] ?? 'fifo',
            $data['variant_id'] ?? null,
        );
    }

    /**
     * Allocate batch/lots for the given product and warehouse using the configured strategy.
     *
     * @param  string  $strategy  'fifo' | 'fefo' | 'lifo'
     * @return Collection<int, array{batch_lot_id: string, quantity: float}>
     *
     * @throws InsufficientStockException
     */
    public function allocate(
        string $productId,
        string $warehouseId,
        float $quantity,
        string $strategy,
        ?string $variantId = null,
    ): Collection {
        /** @var BatchLotRepositoryInterface $repo */
        $repo = $this->repository;

        // Fetch available batch/lots for the product, warehouse, and optional variant
        $batchLots = $repo->findAvailableForAllocation($productId, $warehouseId, $variantId);

        $strategyImpl = $this->strategies[$strategy] ?? $this->strategies['fifo'];

        return $strategyImpl->allocate($batchLots, $quantity);
    }

    /**
     * Register a custom allocation strategy at runtime.
     */
    public function registerStrategy(string $name, AllocationStrategyInterface $strategy): void
    {
        $this->strategies[$name] = $strategy;
    }
}
