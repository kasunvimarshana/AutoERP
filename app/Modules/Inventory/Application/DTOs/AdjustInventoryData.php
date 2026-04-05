<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDTO;

class AdjustInventoryData extends BaseDTO
{
    public int $tenant_id;
    public int $product_id;
    public int $warehouse_id;
    public ?int $location_id = null;
    public float $new_quantity;
    public ?string $reason = null;
}
