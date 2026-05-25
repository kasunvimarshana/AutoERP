<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\CycleCountLines;

use Modules\Core\Application\Results\Result;

interface CreateCycleCountLineServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}