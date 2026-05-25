<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\Payslips;

use Modules\Core\Application\Results\Result;

interface DeletePayslipServiceInterface
{
    public function execute(int|string $id): Result;
}