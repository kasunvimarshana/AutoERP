<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

final class BatchLotData extends BaseDto
{
    public int $product_id;

    public string $batch_number;

    public ?string $lot_number = null;

    public ?string $manufacture_date = null;

    public ?string $expiry_date = null;

    public float $initial_quantity = 0.0;

    public float $remaining_quantity = 0.0;

    public ?string $supplier_batch = null;

    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'product_id'         => ['required', 'integer'],
            'batch_number'       => ['required', 'string', 'max:100'],
            'lot_number'         => ['nullable', 'string', 'max:100'],
            'manufacture_date'   => ['nullable', 'date'],
            'expiry_date'        => ['nullable', 'date'],
            'initial_quantity'   => ['numeric', 'min:0'],
            'remaining_quantity' => ['numeric', 'min:0'],
            'supplier_batch'     => ['nullable', 'string', 'max:100'],
            'metadata'           => ['nullable', 'array'],
        ];
    }
}
