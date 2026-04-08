<?php

declare(strict_types=1);

namespace Modules\UoM\Application\Contracts;

use Modules\Core\Application\Contracts\ReadServiceInterface;
use Modules\UoM\Domain\Entities\UomConversion;

interface FindUomConversionServiceInterface extends ReadServiceInterface
{
    public function findConversion(int $tenantId, int $fromUomId, int $toUomId): ?UomConversion;
}
