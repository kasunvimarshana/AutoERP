<?php
namespace Modules\UoM\Application\Services;

use Modules\UoM\Application\Contracts\CreateUnitOfMeasureServiceInterface;
use Modules\UoM\Application\DTOs\UnitOfMeasureData;
use Modules\UoM\Domain\Entities\UnitOfMeasure;
use Modules\UoM\Domain\Events\UnitOfMeasureCreated;
use Modules\UoM\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;

class CreateUnitOfMeasureService implements CreateUnitOfMeasureServiceInterface
{
    public function __construct(
        private readonly UnitOfMeasureRepositoryInterface $repository,
    ) {}

    public function execute(UnitOfMeasureData $data): UnitOfMeasure
    {
        $uom = $this->repository->create($data->toArray());
        event(new UnitOfMeasureCreated($data->tenantId, $uom->id));
        return $uom;
    }
}
