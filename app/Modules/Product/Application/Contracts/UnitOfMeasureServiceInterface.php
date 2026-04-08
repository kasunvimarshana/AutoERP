<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

use Modules\Product\Application\DTOs\UnitOfMeasureData;

interface UnitOfMeasureServiceInterface
{
    public function create(UnitOfMeasureData $dto): mixed;
    public function findByAbbreviation(string $abbreviation, int $tenantId): mixed;
}
