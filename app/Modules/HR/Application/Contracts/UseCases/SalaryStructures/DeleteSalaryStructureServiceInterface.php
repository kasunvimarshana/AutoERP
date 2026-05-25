<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\SalaryStructures;

use Modules\Core\Application\Results\Result;

interface DeleteSalaryStructureServiceInterface
{
    public function execute(int|string $id): Result;
}