<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\SalaryStructureLines;

use Modules\Core\Application\Results\Result;

interface CreateSalaryStructureLineServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}