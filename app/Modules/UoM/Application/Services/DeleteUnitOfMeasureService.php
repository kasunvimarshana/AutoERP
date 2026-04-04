<?php
namespace Modules\UoM\Application\Services;

use Modules\UoM\Application\Contracts\DeleteUnitOfMeasureServiceInterface;
use Modules\UoM\Domain\Events\UnitOfMeasureDeleted;
use Modules\UoM\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;

class DeleteUnitOfMeasureService implements DeleteUnitOfMeasureServiceInterface
{
    public function __construct(
        private readonly UnitOfMeasureRepositoryInterface $repository,
    ) {}

    public function execute(int $id): bool
    {
        $uom = $this->repository->findById($id);
        if (!$uom) {
            throw new \DomainException("UnitOfMeasure not found: {$id}");
        }
        $result = $this->repository->delete($uom);
        event(new UnitOfMeasureDeleted($uom->tenantId, $id));
        return $result;
    }
}
