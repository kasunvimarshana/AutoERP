<?php

declare(strict_types=1);

namespace Modules\Order\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class ReturnOrderData extends BaseDto
{
    public ?string $id = null;
    public ?string $returnNumber = null;
    public string $returnDate = '';
    public string $type = 'sales_return';
    public ?string $sourceOrderType = null;
    public ?string $sourceOrderId = null;
    public string $status = 'draft';
    public string $currencyCode = 'USD';
    public float $restockingFee = 0.0;
    public ?string $reason = null;
    public ?string $notes = null;
    public string $resolution = 'refund';
    public ?string $warehouseId = null;
    public array $lines = [];
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'return_date' => ['required', 'date'],
            'type'        => ['required', 'string', 'in:sales_return,purchase_return'],
            'resolution'  => ['sometimes', 'string', 'in:refund,credit_memo,exchange'],
            'lines'       => ['required', 'array', 'min:1'],
        ];
    }
}
