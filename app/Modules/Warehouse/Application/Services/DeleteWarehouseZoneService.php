<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Warehouse\Application\Contracts\DeleteWarehouseZoneServiceInterface;
use Modules\Warehouse\Domain\Events\WarehouseZoneDeleted;
use Modules\Warehouse\Domain\Exceptions\WarehouseZoneNotFoundException;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseZoneRepositoryInterface;

class DeleteWarehouseZoneService extends BaseService implements DeleteWarehouseZoneServiceInterface
{
    private WarehouseZoneRepositoryInterface $zoneRepository;

    public function __construct(WarehouseZoneRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->zoneRepository = $repository;
    }

    protected function handle(array $data): bool
    {
        $id   = $data['id'];
        $zone = $this->zoneRepository->find($id);
        if (! $zone) {
            throw new WarehouseZoneNotFoundException($id);
        }
        $tenantId = $zone->getTenantId();
        $deleted  = $this->zoneRepository->delete($id);
        if ($deleted) {
            $this->addEvent(new WarehouseZoneDeleted($id, $tenantId));
        }

        return $deleted;
    }
}
