<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Product\Domain\Entities\UomConversion;

interface UomConversionRepositoryInterface extends RepositoryInterface
{
    public function save(UomConversion $uomConversion): UomConversion;

    public function findByUomPair(int $fromUomId, int $toUomId): ?UomConversion;

    public function find($id, array $columns = ['*']): ?UomConversion;
}
