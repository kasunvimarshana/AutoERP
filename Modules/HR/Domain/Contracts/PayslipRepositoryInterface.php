<?php

namespace Modules\HR\Domain\Contracts;

use Illuminate\Support\Collection;
use Modules\Shared\Domain\Contracts\RepositoryInterface;

interface PayslipRepositoryInterface extends RepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): object;
    public function findByPayrollRun(string $payrollRunId): Collection;
}
