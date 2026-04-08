<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class StockMovementData extends BaseDto
{
    public string $productId = '';
    public ?string $variantId = null;
    public string $warehouseId = '';
    public ?string $locationId = null;
    public string $type = 'adjustment_in';
    public float $quantity = 0.0;
    public float $unitCost = 0.0;
    public ?string $referenceType = null;
    public ?string $referenceId = null;
    public string $unitOfMeasure = 'piece';
    public ?string $notes = null;

    public function rules(): array
    {
        return [
            'product_id'   => ['required', 'string'],
            'warehouse_id' => ['required', 'string'],
            'type'         => ['required', 'string'],
            'quantity'     => ['required', 'numeric'],
            'unit_cost'    => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
