<?php

namespace Modules\Returns\Application\Contracts;

use Modules\Returns\Domain\Entities\StockReturn;

interface ProcessReturnInventoryAdjustmentServiceInterface
{
    public function execute(StockReturn $return): void;
}
