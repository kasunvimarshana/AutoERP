<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Collection;
use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\FindInventoryValuationLayerServiceInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryValuationLayerRepositoryInterface;

class FindInventoryValuationLayerService extends BaseService implements FindInventoryValuationLayerServiceInterface
{
    public function __construct(private readonly InventoryValuationLayerRepositoryInterface $layerRepository)
    {
        parent::__construct($layerRepository);
    }

    public function findOpenLayers(int $tenantId, int $productId, string $valuationMethod): Collection
    {
        return $this->layerRepository->findOpenLayers($tenantId, $productId, $valuationMethod);
    }

    public function findByProduct(int $tenantId, int $productId): Collection
    {
        return $this->layerRepository->findByProduct($tenantId, $productId);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
