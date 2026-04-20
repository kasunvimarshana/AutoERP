<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\FindCostCenterServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\CostCenterRepositoryInterface;

class FindCostCenterService extends BaseService implements FindCostCenterServiceInterface
{
    public function __construct(private readonly CostCenterRepositoryInterface $costCenterRepository)
    {
        parent::__construct($costCenterRepository);
    }

    protected function handle(array $data): mixed
    {
        return null;
    }
}
