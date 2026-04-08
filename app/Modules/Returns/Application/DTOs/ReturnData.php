<?php

declare(strict_types=1);

namespace Modules\Returns\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

final class ReturnData extends BaseDto
{
    public string $type;

    public ?int $original_order_id = null;

    public ?int $supplier_id = null;

    public ?int $customer_id = null;

    public ?int $warehouse_id = null;

    public string $return_date;

    public string $reason;

    public ?int $restock_location_id = null;

    public float $fee_amount = 0.0;

    public ?string $fee_description = null;

    public ?string $notes = null;

    public ?string $internal_notes = null;

    public ?array $metadata = null;

    /** @var array<int, array<string, mixed>> */
    public array $lines = [];

    public function rules(): array
    {
        return [
            'type'                            => ['required', 'string', 'in:purchase_return,sale_return'],
            'original_order_id'               => ['nullable', 'integer', 'min:1'],
            'supplier_id'                     => ['nullable', 'integer', 'min:1'],
            'customer_id'                     => ['nullable', 'integer', 'min:1'],
            'warehouse_id'                    => ['nullable', 'integer', 'min:1'],
            'return_date'                     => ['required', 'date'],
            'reason'                          => ['required', 'string', 'in:defective,wrong_item,damaged,overdelivery,quality_issue,other'],
            'restock_location_id'             => ['nullable', 'integer', 'min:1'],
            'fee_amount'                      => ['numeric', 'min:0'],
            'fee_description'                 => ['nullable', 'string', 'max:255'],
            'notes'                           => ['nullable', 'string'],
            'internal_notes'                  => ['nullable', 'string'],
            'metadata'                        => ['nullable', 'array'],
            'lines'                           => ['sometimes', 'array'],
            'lines.*.product_id'              => ['required_with:lines', 'integer', 'min:1'],
            'lines.*.variant_id'              => ['nullable', 'integer', 'min:1'],
            'lines.*.quantity_requested'      => ['required_with:lines', 'numeric', 'min:0.000001'],
            'lines.*.unit_price'              => ['required_with:lines', 'numeric', 'min:0'],
            'lines.*.quality_check_result'    => ['nullable', 'string', 'in:passed,failed,pending,quarantine'],
            'lines.*.restock_action'          => ['nullable', 'string', 'in:restock,scrap,quarantine,return_to_supplier'],
        ];
    }
}
