<?php

declare(strict_types=1);

namespace Modules\UOM\Application\Contracts\UseCases\UnitOfMeasures;

use Modules\Core\Application\Results\Result;

interface GetUnitOfMeasureServiceInterface
{
    public function execute(int|string $id): Result;
}