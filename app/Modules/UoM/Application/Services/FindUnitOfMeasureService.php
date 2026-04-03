<?php

declare(strict_types=1);

namespace Modules\UoM\Application\Services;

use Illuminate\Support\Collection;
use Modules\Core\Application\Services\BaseService;
use Modules\UoM\Application\Contracts\FindUnitOfMeasureServiceInterface;
use Modules\UoM\Domain\Entities\UnitOfMeasure;
use Modules\UoM\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;

class FindUnitOfMeasureService extends BaseService implements FindUnitOfMeasureServiceInterface
{
    public function __construct(private readonly UnitOfMeasureRepositoryInterface $unitRepository)
    {
        parent::__construct($unitRepository);
    }

    public function findByCode(int $tenantId, string $code): ?UnitOfMeasure
    {
        return $this->unitRepository->findByCode($tenantId, $code);
    }

    public function findByCategory(int $tenantId, int $categoryId): Collection
    {
        return $this->unitRepository->findByCategory($tenantId, $categoryId);
    }

    public function findBaseUnit(int $tenantId, int $categoryId): ?UnitOfMeasure
    {
        return $this->unitRepository->findBaseUnit($tenantId, $categoryId);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
