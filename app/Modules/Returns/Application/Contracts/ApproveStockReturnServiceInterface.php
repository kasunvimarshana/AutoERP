<?php

namespace Modules\Returns\Application\Contracts;

use Modules\Returns\Domain\Entities\StockReturn;

interface ApproveStockReturnServiceInterface
{
    public function execute(StockReturn $return, int $approvedBy): StockReturn;
}
