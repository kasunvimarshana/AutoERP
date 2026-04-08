<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Warehouse\Application\Contracts\OrganizationUnitServiceInterface;
use Modules\Warehouse\Domain\Contracts\Repositories\OrganizationUnitRepositoryInterface;

class OrganizationUnitService extends BaseService implements OrganizationUnitServiceInterface
{
    public function __construct(OrganizationUnitRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    protected function handle(array $data): mixed
    {
        return $this->repository->create($data);
    }
}
