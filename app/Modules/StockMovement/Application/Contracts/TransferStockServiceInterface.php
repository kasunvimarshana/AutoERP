<?php
namespace Modules\StockMovement\Application\Contracts;
use Modules\StockMovement\Application\DTOs\TransferStockData;
interface TransferStockServiceInterface
{
    public function execute(TransferStockData $data): array;
}
