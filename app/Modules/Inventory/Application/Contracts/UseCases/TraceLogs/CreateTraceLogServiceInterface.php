<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\TraceLogs;

use Modules\Core\Application\Results\Result;

interface CreateTraceLogServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}