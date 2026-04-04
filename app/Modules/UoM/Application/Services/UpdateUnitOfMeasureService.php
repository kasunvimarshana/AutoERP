<?php
namespace Modules\UoM\Application\Services;

use Modules\UoM\Application\Contracts\UpdateUnitOfMeasureServiceInterface;
use Modules\UoM\Application\DTOs\UnitOfMeasureData;
use Modules\UoM\Domain\Entities\UnitOfMeasure;
use Modules\UoM\Domain\Events\UnitOfMeasureUpdated;
use Modules\UoM\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;

class UpdateUnitOfMeasureService implements UpdateUnitOfMeasureServiceInterface
{
    public function __construct(
        private readonly UnitOfMeasureRepositoryInterface $repository,
    ) {}

    public function execute(int $id, UnitOfMeasureData $data): UnitOfMeasure
    {
        $uom = $this->repository->findById($id);
        if (!$uom) {
            throw new \DomainException("UnitOfMeasure not found: {$id}");
        }
        $updated = $this->repository->update($uom, $data->toArray());
        event(new UnitOfMeasureUpdated($data->tenantId, $id));
        return $updated;
    }
}
