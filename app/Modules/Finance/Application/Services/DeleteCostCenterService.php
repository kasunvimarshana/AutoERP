<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\DeleteCostCenterServiceInterface;
use Modules\Finance\Domain\Exceptions\CostCenterNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\CostCenterRepositoryInterface;

class DeleteCostCenterService extends BaseService implements DeleteCostCenterServiceInterface
{
    public function __construct(private readonly CostCenterRepositoryInterface $costCenterRepository)
    {
        parent::__construct($costCenterRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $costCenter = $this->costCenterRepository->find($id);
        if (! $costCenter) {
            throw new CostCenterNotFoundException($id);
        }

        return $this->costCenterRepository->delete($id);
    }
}
