<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\SalaryStructures;

use Modules\Core\Application\Results\Result;

interface GetSalaryStructureServiceInterface
{
    public function execute(int|string $id): Result;
}