<?php
namespace Modules\Inventory\Application\Contracts;

use Modules\Inventory\Application\DTOs\ConsumeValuationLayersData;

interface ConsumeValuationLayersServiceInterface
{
    /**
     * Consume quantity from inventory valuation layers using FIFO, LIFO, or weighted-average.
     *
     * @return float Total cost consumed
     * @throws \DomainException if insufficient stock exists in the layers
     */
    public function execute(ConsumeValuationLayersData $data): float;
}
