<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Warehouse\Application\Contracts\FindWarehouseServiceInterface;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;

class FindWarehouseService extends BaseService implements FindWarehouseServiceInterface
{
    protected array $allowedSortColumns = ['id', 'name', 'code', 'type', 'is_active', 'is_default', 'created_at', 'updated_at'];

    protected array $allowedFilterFields = ['tenant_id', 'org_unit_id', 'name', 'code', 'type', 'is_active', 'is_default'];

    public function __construct(WarehouseRepositoryInterface $warehouseRepository)
    {
        parent::__construct($warehouseRepository);
    }

    protected function handle(array $data): mixed
    {
        return null;
    }
}
