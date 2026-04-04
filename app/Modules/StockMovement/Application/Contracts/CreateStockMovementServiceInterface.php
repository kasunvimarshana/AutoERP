<?php
namespace Modules\StockMovement\Application\Contracts;
use Modules\StockMovement\Application\DTOs\StockMovementData;
use Modules\StockMovement\Domain\Entities\StockMovement;
interface CreateStockMovementServiceInterface
{
    public function execute(StockMovementData $data): StockMovement;
}
