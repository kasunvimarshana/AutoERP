<?php

declare(strict_types=1);

namespace Modules\Order\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

final class OrderLineData extends BaseDto
{
    public int $product_id;

    public ?int $variant_id = null;

    public ?string $description = null;

    public float $quantity;

    public ?string $unit_of_measure = null;

    public float $unit_price;

    public float $discount_percent = 0.0;

    public float $tax_rate = 0.0;

    public ?int $batch_lot_id = null;

    public ?int $serial_number_id = null;

    public ?string $notes = null;

    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'product_id'       => ['required', 'integer', 'min:1'],
            'variant_id'       => ['nullable', 'integer', 'min:1'],
            'description'      => ['nullable', 'string', 'max:500'],
            'quantity'         => ['required', 'numeric', 'min:0.000001'],
            'unit_of_measure'  => ['nullable', 'string', 'max:50'],
            'unit_price'       => ['required', 'numeric', 'min:0'],
            'discount_percent' => ['numeric', 'min:0', 'max:100'],
            'tax_rate'         => ['numeric', 'min:0', 'max:100'],
            'batch_lot_id'     => ['nullable', 'integer', 'min:1'],
            'serial_number_id' => ['nullable', 'integer', 'min:1'],
            'notes'            => ['nullable', 'string'],
            'metadata'         => ['nullable', 'array'],
        ];
    }
}
