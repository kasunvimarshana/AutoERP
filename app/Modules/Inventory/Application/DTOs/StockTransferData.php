<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

final class StockTransferData extends BaseDto
{
    public int $product_id;

    public int $from_location_id;

    public int $to_location_id;

    public ?int $variant_id = null;

    public ?int $batch_lot_id = null;

    public float $quantity;

    public ?string $reference = null;

    public ?string $notes = null;

    public function rules(): array
    {
        return [
            'product_id'       => ['required', 'integer'],
            'from_location_id' => ['required', 'integer'],
            'to_location_id'   => ['required', 'integer', 'different:from_location_id'],
            'variant_id'       => ['nullable', 'integer'],
            'batch_lot_id'     => ['nullable', 'integer'],
            'quantity'         => ['required', 'numeric', 'min:0.001'],
            'reference'        => ['nullable', 'string', 'max:255'],
            'notes'            => ['nullable', 'string'],
        ];
    }
}
