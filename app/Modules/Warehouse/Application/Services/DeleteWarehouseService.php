<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Warehouse\Application\Contracts\DeleteWarehouseServiceInterface;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;

class DeleteWarehouseService extends BaseService implements DeleteWarehouseServiceInterface
{
    public function __construct(private readonly WarehouseRepositoryInterface $warehouseRepository)
    {
        parent::__construct($warehouseRepository);
    }

    protected function handle(array $data): bool
    {
        return $this->warehouseRepository->delete((int) $data['id']);
    }
}
