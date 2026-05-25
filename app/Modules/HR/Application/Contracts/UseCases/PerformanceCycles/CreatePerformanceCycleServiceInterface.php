<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\PerformanceCycles;

use Modules\Core\Application\Results\Result;

interface CreatePerformanceCycleServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}