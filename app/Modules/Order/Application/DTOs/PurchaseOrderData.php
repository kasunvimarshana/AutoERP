<?php

declare(strict_types=1);

namespace Modules\Order\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class PurchaseOrderData extends BaseDto
{
    public ?string $id = null;
    public ?string $orderNumber = null;
    public string $orderDate = '';
    public ?string $expectedDate = null;
    public string $supplierId = '';
    public ?string $warehouseId = null;
    public string $status = 'draft';
    public string $currencyCode = 'USD';
    public float $exchangeRate = 1.0;
    public float $discountAmount = 0.0;
    public float $shippingAmount = 0.0;
    public ?string $notes = null;
    public array $lines = [];
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'order_date'  => ['required', 'date'],
            'supplier_id' => ['required', 'string'],
            'lines'       => ['required', 'array', 'min:1'],
            'lines.*.product_id'       => ['required', 'string'],
            'lines.*.quantity_ordered' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_cost'        => ['required', 'numeric', 'min:0'],
        ];
    }
}
