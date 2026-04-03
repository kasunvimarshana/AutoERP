<?php

declare(strict_types=1);

namespace Modules\UoM\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\UoM\Domain\Entities\UomConversion;

interface UomConversionRepositoryInterface extends RepositoryInterface
{
    public function save(UomConversion $conversion): UomConversion;

    public function findConversion(int $tenantId, int $fromUomId, int $toUomId): ?UomConversion;
}
