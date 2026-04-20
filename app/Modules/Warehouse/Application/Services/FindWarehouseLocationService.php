<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Warehouse\Application\Contracts\FindWarehouseLocationServiceInterface;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseLocationRepositoryInterface;

class FindWarehouseLocationService extends BaseService implements FindWarehouseLocationServiceInterface
{
    protected array $allowedSortColumns = ['id', 'name', 'code', 'path', 'depth', 'type', 'is_active', 'is_pickable', 'is_receivable', 'created_at', 'updated_at'];

    protected array $allowedFilterFields = ['tenant_id', 'warehouse_id', 'parent_id', 'name', 'code', 'type', 'is_active', 'is_pickable', 'is_receivable'];

    public function __construct(WarehouseLocationRepositoryInterface $warehouseLocationRepository)
    {
        parent::__construct($warehouseLocationRepository);
    }

    protected function handle(array $data): mixed
    {
        return null;
    }
}
