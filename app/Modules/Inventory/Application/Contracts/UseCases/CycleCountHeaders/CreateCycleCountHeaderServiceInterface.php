<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\CycleCountHeaders;

use Modules\Core\Application\Results\Result;

interface CreateCycleCountHeaderServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}