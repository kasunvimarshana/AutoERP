<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Modules\Inventory\Application\Contracts\FindCycleCountServiceInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\CycleCountRepositoryInterface;

class FindCycleCountService implements FindCycleCountServiceInterface
{
    public function __construct(private readonly CycleCountRepositoryInterface $cycleCountRepository) {}

    public function find(int $tenantId, int $countId): mixed
    {
        return $this->cycleCountRepository->findById($tenantId, $countId);
    }

    public function list(int $tenantId, int $perPage = 15, int $page = 1): mixed
    {
        return $this->cycleCountRepository->paginate($tenantId, $perPage, $page);
    }
}
