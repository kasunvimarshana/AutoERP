<?php

namespace Modules\Returns\Application\Contracts;

use Modules\Returns\Domain\Entities\StockReturn;

interface CancelStockReturnServiceInterface
{
    public function execute(StockReturn $return): StockReturn;
}
