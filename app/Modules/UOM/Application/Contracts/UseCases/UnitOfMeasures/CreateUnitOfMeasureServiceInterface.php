<?php

declare(strict_types=1);

namespace Modules\UOM\Application\Contracts\UseCases\UnitOfMeasures;

use Modules\Core\Application\Results\Result;

interface CreateUnitOfMeasureServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}