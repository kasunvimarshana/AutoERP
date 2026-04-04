<?php

namespace Modules\Returns\Application\Contracts;

use Modules\Returns\Domain\Entities\StockReturn;

interface CompleteStockReturnServiceInterface
{
    public function execute(StockReturn $return, int $completedBy): StockReturn;
}
