<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\PayslipLines;

use Modules\Core\Application\Results\Result;

interface DeletePayslipLineServiceInterface
{
    public function execute(int|string $id): Result;
}