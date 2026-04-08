<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Collection;
use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\FindInventoryCycleCountLineServiceInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountLineRepositoryInterface;

class FindInventoryCycleCountLineService extends BaseService implements FindInventoryCycleCountLineServiceInterface
{
    public function __construct(private readonly InventoryCycleCountLineRepositoryInterface $lineRepository)
    {
        parent::__construct($lineRepository);
    }

    public function findByCycleCount(int $tenantId, int $cycleCountId): Collection
    {
        return $this->lineRepository->findByCycleCount($tenantId, $cycleCountId);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
