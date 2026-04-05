<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Application\DTOs\IssueStockData;
use Modules\Inventory\Domain\Entities\InventoryLevel;

interface IssueStockServiceInterface
{
    public function execute(IssueStockData $data): InventoryLevel;
}
