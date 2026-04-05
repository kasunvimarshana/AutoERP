<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class ReceiveStockData extends BaseDTO
{
    public int $tenant_id;
    public int $product_id;
    public int $warehouse_id;
    public ?int $location_id = null;
    public float $quantity;
    public float $unit_cost;
    public string $valuation_method = 'fifo';
    public ?string $batch_number = null;
    public ?string $lot_number = null;
    public ?string $serial_number = null;
    public ?string $expires_at = null;
    public ?string $manufactured_at = null;
    public ?string $reference = null;
}
