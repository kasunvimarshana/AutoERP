<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Warehouse\Application\Contracts\DeleteWarehouseServiceInterface;
use Modules\Warehouse\Domain\Events\WarehouseDeleted;
use Modules\Warehouse\Domain\Exceptions\WarehouseNotFoundException;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;

class DeleteWarehouseService extends BaseService implements DeleteWarehouseServiceInterface
{
    private WarehouseRepositoryInterface $warehouseRepository;

    public function __construct(WarehouseRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->warehouseRepository = $repository;
    }

    protected function handle(array $data): bool
    {
        $id        = $data['id'];
        $warehouse = $this->warehouseRepository->find($id);
        if (! $warehouse) {
            throw new WarehouseNotFoundException($id);
        }
        $tenantId = $warehouse->getTenantId();
        $deleted  = $this->warehouseRepository->delete($id);
        if ($deleted) {
            $this->addEvent(new WarehouseDeleted($id, $tenantId));
        }

        return $deleted;
    }
}
