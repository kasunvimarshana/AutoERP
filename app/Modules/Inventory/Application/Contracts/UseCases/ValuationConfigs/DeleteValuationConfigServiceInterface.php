<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\UseCases\ValuationConfigs;

use Modules\Core\Application\Results\Result;

interface DeleteValuationConfigServiceInterface
{
    public function execute(int|string $id): Result;
}