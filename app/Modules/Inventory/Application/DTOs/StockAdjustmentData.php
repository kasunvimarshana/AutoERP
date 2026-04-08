<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

final class StockAdjustmentData extends BaseDto
{
    public int $product_id;

    public int $location_id;

    public ?int $variant_id = null;

    public ?int $batch_lot_id = null;

    public ?int $serial_number_id = null;

    public float $quantity;

    public string $movement_type = 'adjustment';

    public ?float $unit_cost = null;

    public ?string $reference = null;

    public ?string $notes = null;

    public function rules(): array
    {
        return [
            'product_id'       => ['required', 'integer'],
            'location_id'      => ['required', 'integer'],
            'variant_id'       => ['nullable', 'integer'],
            'batch_lot_id'     => ['nullable', 'integer'],
            'serial_number_id' => ['nullable', 'integer'],
            'quantity'         => ['required', 'numeric'],
            'movement_type'    => ['required', 'string', 'in:receipt,issue,transfer,adjustment,return_in,return_out,scrap'],
            'unit_cost'        => ['nullable', 'numeric', 'min:0'],
            'reference'        => ['nullable', 'string', 'max:255'],
            'notes'            => ['nullable', 'string'],
        ];
    }
}
