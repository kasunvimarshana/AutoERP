<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CreateCostCenterServiceInterface;
use Modules\Finance\Application\DTOs\CostCenterData;
use Modules\Finance\Domain\Entities\CostCenter;
use Modules\Finance\Domain\RepositoryInterfaces\CostCenterRepositoryInterface;

class CreateCostCenterService extends BaseService implements CreateCostCenterServiceInterface
{
    public function __construct(private readonly CostCenterRepositoryInterface $costCenterRepository)
    {
        parent::__construct($costCenterRepository);
    }

    protected function handle(array $data): CostCenter
    {
        $dto = CostCenterData::fromArray($data);

        $costCenter = new CostCenter(
            tenantId: $dto->tenantId,
            code: $dto->code,
            name: $dto->name,
            parentId: $dto->parent_id,
            description: $dto->description,
            isActive: $dto->isActive,
            path: $dto->path,
            depth: $dto->depth,
        );

        return $this->costCenterRepository->save($costCenter);
    }
}
