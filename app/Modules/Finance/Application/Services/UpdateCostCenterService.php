<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\UpdateCostCenterServiceInterface;
use Modules\Finance\Application\DTOs\CostCenterData;
use Modules\Finance\Domain\Entities\CostCenter;
use Modules\Finance\Domain\Exceptions\CostCenterNotFoundException;
use Modules\Core\Domain\Exceptions\ConcurrentModificationException;
use Modules\Finance\Domain\RepositoryInterfaces\CostCenterRepositoryInterface;

class UpdateCostCenterService extends BaseService implements UpdateCostCenterServiceInterface
{
    public function __construct(private readonly CostCenterRepositoryInterface $costCenterRepository)
    {
        parent::__construct($costCenterRepository);
    }

    protected function handle(array $data): CostCenter
    {
        $dto = CostCenterData::fromArray($data);

        /** @var CostCenter|null $costCenter */
        $costCenter = $this->costCenterRepository->find((int) $dto->id);
        if (! $costCenter) {
            throw new CostCenterNotFoundException((int) $dto->id);
        }


        if ($dto->rowVersion !== $costCenter->getRowVersion()) {
            throw new ConcurrentModificationException('CostCenter', (int) $dto->id);
        }

        $costCenter->update(
            code: $dto->code,
            name: $dto->name,
            parentId: $dto->parent_id,
            description: $dto->description,
            isActive: $dto->is_active,
        );

        return $this->costCenterRepository->save($costCenter);
    }
}
