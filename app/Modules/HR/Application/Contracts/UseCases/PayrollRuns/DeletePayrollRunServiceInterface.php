<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\PayrollRuns;

use Modules\Core\Application\Results\Result;

interface DeletePayrollRunServiceInterface
{
    public function execute(int|string $id): Result;
}