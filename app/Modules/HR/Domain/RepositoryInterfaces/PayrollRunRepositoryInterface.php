<?php

declare(strict_types=1);

namespace Modules\HR\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\HR\Domain\Entities\PayrollRun;

interface PayrollRunRepositoryInterface extends RepositoryInterface
{
    public function save(PayrollRun $run): PayrollRun;

    public function find(int|string $id, array $columns = ['*']): ?PayrollRun;

    public function findByTenantAndPeriod(int $tenantId, string $periodStart, string $periodEnd): ?PayrollRun;
}
