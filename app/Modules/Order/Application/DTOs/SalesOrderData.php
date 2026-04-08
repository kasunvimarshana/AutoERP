<?php

declare(strict_types=1);

namespace Modules\Order\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class SalesOrderData extends BaseDto
{
    public ?string $id = null;
    public ?string $orderNumber = null;
    public string $orderDate = '';
    public ?string $requiredDate = null;
    public string $customerId = '';
    public string $status = 'draft';
    public string $currencyCode = 'USD';
    public float $exchangeRate = 1.0;
    public float $discountAmount = 0.0;
    public float $shippingAmount = 0.0;
    public ?string $paymentTerms = null;
    public ?string $notes = null;
    public array $lines = [];
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'order_date'  => ['required', 'date'],
            'customer_id' => ['required', 'string'],
            'lines'       => ['required', 'array', 'min:1'],
            'lines.*.product_id'       => ['required', 'string'],
            'lines.*.quantity_ordered' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price'       => ['required', 'numeric', 'min:0'],
        ];
    }
}
