<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\SalaryStructureLines;

use Modules\Core\Application\Results\Result;

interface DeleteSalaryStructureLineServiceInterface
{
    public function execute(int|string $id): Result;
}