<?php

namespace Modules\Returns\Application\Contracts;

use Modules\Returns\Domain\Entities\StockReturnLine;

interface PassQualityCheckServiceInterface
{
    public function execute(StockReturnLine $line, int $checkedBy): StockReturnLine;
}
