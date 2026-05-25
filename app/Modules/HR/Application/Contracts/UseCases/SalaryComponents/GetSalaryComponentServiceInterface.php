<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\SalaryComponents;

use Modules\Core\Application\Results\Result;

interface GetSalaryComponentServiceInterface
{
    public function execute(int|string $id): Result;
}