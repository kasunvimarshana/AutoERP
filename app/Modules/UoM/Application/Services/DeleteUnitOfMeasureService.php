<?php

declare(strict_types=1);

namespace Modules\UoM\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\UoM\Application\Contracts\DeleteUnitOfMeasureServiceInterface;
use Modules\UoM\Domain\Events\UnitOfMeasureDeleted;
use Modules\UoM\Domain\Exceptions\UnitOfMeasureNotFoundException;
use Modules\UoM\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;

class DeleteUnitOfMeasureService extends BaseService implements DeleteUnitOfMeasureServiceInterface
{
    private UnitOfMeasureRepositoryInterface $unitRepository;

    public function __construct(UnitOfMeasureRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->unitRepository = $repository;
    }

    protected function handle(array $data): bool
    {
        $id   = $data['id'];
        $unit = $this->unitRepository->find($id);

        if (! $unit) {
            throw new UnitOfMeasureNotFoundException($id);
        }

        $tenantId = $unit->getTenantId();
        $deleted  = $this->unitRepository->delete($id);

        if ($deleted) {
            $this->addEvent(new UnitOfMeasureDeleted($id, $tenantId));
        }

        return $deleted;
    }
}
