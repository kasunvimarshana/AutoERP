<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class IssueStockData extends BaseDTO
{
    public int $tenant_id;
    public int $product_id;
    public int $warehouse_id;
    public ?int $location_id = null;
    public float $quantity;
    public string $allocation_strategy = 'fifo';  // fifo|lifo|fefo
    public ?string $reference = null;
}
