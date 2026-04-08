<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Warehouse\Application\Contracts\WarehouseLocationServiceInterface;
use Modules\Warehouse\Domain\Contracts\Repositories\WarehouseLocationRepositoryInterface;

class WarehouseLocationService extends BaseService implements WarehouseLocationServiceInterface
{
    public function __construct(WarehouseLocationRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    protected function handle(array $data): mixed
    {
        return $this->repository->create($data);
    }
}
