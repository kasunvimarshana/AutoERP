<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\PerformanceCycles;

use Modules\Core\Application\Results\Result;

interface GetPerformanceCycleServiceInterface
{
    public function execute(int|string $id): Result;
}