<?php

declare(strict_types=1);

namespace Modules\StockMovement\Application\Services;

use Illuminate\Support\Collection;
use Modules\Core\Application\Services\BaseService;
use Modules\StockMovement\Application\Contracts\FindStockMovementServiceInterface;
use Modules\StockMovement\Domain\Entities\StockMovement;
use Modules\StockMovement\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;

class FindStockMovementService extends BaseService implements FindStockMovementServiceInterface
{
    public function __construct(private readonly StockMovementRepositoryInterface $movementRepository)
    {
        parent::__construct($movementRepository);
    }

    public function findByProduct(int $tenantId, int $productId): Collection
    {
        return $this->movementRepository->findByProduct($tenantId, $productId);
    }

    public function findByMovementType(int $tenantId, string $type): Collection
    {
        return $this->movementRepository->findByMovementType($tenantId, $type);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
