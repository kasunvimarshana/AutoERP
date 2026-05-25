<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\SalaryComponents;

use Modules\Core\Application\Results\Result;

interface DeleteSalaryComponentServiceInterface
{
    public function execute(int|string $id): Result;
}