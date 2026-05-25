<?php

declare(strict_types=1);

namespace Modules\UOM\Application\Contracts\UseCases\UnitOfMeasures;

use Modules\Core\Application\Results\Result;

interface DeleteUnitOfMeasureServiceInterface
{
    public function execute(int|string $id): Result;
}