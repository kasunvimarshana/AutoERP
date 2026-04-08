<?php

declare(strict_types=1);

namespace Modules\Returns\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

final class ReturnLineData extends BaseDto
{
    public ?int $order_line_id = null;

    public int $product_id;

    public ?int $variant_id = null;

    public ?int $batch_lot_id = null;

    public ?int $serial_number_id = null;

    public float $quantity_requested;

    public float $unit_price;

    public string $quality_check_result = 'pending';

    public ?string $quality_notes = null;

    public ?string $condition_notes = null;

    public string $restock_action = 'restock';

    public function rules(): array
    {
        return [
            'order_line_id'        => ['nullable', 'integer', 'min:1'],
            'product_id'           => ['required', 'integer', 'min:1'],
            'variant_id'           => ['nullable', 'integer', 'min:1'],
            'batch_lot_id'         => ['nullable', 'integer', 'min:1'],
            'serial_number_id'     => ['nullable', 'integer', 'min:1'],
            'quantity_requested'   => ['required', 'numeric', 'min:0.000001'],
            'unit_price'           => ['required', 'numeric', 'min:0'],
            'quality_check_result' => ['string', 'in:passed,failed,pending,quarantine'],
            'quality_notes'        => ['nullable', 'string'],
            'condition_notes'      => ['nullable', 'string'],
            'restock_action'       => ['string', 'in:restock,scrap,quarantine,return_to_supplier'],
        ];
    }
}
