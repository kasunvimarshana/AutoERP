<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Finance\Domain\Entities\CostCenter;

interface CostCenterRepositoryInterface extends RepositoryInterface
{
    public function save(CostCenter $costCenter): CostCenter;

    public function findByTenantAndCode(int $tenantId, string $code): ?CostCenter;
}
