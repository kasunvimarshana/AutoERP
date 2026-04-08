<?php

declare(strict_types=1);

namespace Modules\Order\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

final class OrderData extends BaseDto
{
    public string $type;

    public ?int $supplier_id = null;

    public ?int $customer_id = null;

    public ?int $warehouse_id = null;

    public string $order_date;

    public ?string $expected_date = null;

    public string $currency = 'USD';

    public float $exchange_rate = 1.0;

    public ?array $billing_address = null;

    public ?array $shipping_address = null;

    public ?string $notes = null;

    public ?string $internal_notes = null;

    public ?array $metadata = null;

    /** @var array<int, array<string, mixed>> */
    public array $lines = [];

    public function rules(): array
    {
        return [
            'type'                      => ['required', 'string', 'in:purchase,sale'],
            'supplier_id'               => ['nullable', 'integer', 'min:1'],
            'customer_id'               => ['nullable', 'integer', 'min:1'],
            'warehouse_id'              => ['nullable', 'integer', 'min:1'],
            'order_date'                => ['required', 'date'],
            'expected_date'             => ['nullable', 'date'],
            'currency'                  => ['string', 'size:3'],
            'exchange_rate'             => ['numeric', 'min:0'],
            'billing_address'           => ['nullable', 'array'],
            'shipping_address'          => ['nullable', 'array'],
            'notes'                     => ['nullable', 'string'],
            'internal_notes'            => ['nullable', 'string'],
            'metadata'                  => ['nullable', 'array'],
            'lines'                     => ['sometimes', 'array'],
            'lines.*.product_id'        => ['required_with:lines', 'integer', 'min:1'],
            'lines.*.variant_id'        => ['nullable', 'integer', 'min:1'],
            'lines.*.quantity'          => ['required_with:lines', 'numeric', 'min:0.000001'],
            'lines.*.unit_price'        => ['required_with:lines', 'numeric', 'min:0'],
            'lines.*.discount_percent'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.tax_rate'          => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
