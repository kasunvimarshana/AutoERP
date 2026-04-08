<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\UnitOfMeasureServiceInterface;
use Modules\Product\Application\DTOs\UnitOfMeasureData;
use Modules\Product\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;

class UnitOfMeasureService extends BaseService implements UnitOfMeasureServiceInterface
{
    public function __construct(UnitOfMeasureRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    protected function handle(array $data): mixed
    {
        return $this->repository->create($data);
    }

    public function create(UnitOfMeasureData $dto): mixed
    {
        return $this->execute($dto->toArray());
    }

    public function findByAbbreviation(string $abbreviation, int $tenantId): mixed
    {
        return $this->repository->findByAbbreviation($abbreviation, $tenantId);
    }
}
