<?php

namespace Modules\Returns\Application\Contracts;

use Modules\Returns\Application\DTOs\StockReturnData;
use Modules\Returns\Domain\Entities\StockReturn;

interface CreateStockReturnServiceInterface
{
    public function execute(StockReturnData $data): StockReturn;
}
