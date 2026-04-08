<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class InventorySerialNumberData extends BaseDto
{
    public int $tenantId;

    public int $productId;

    public ?int $variationId = null;

    public ?int $batchId = null;

    public string $serialNumber;

    public ?int $locationId = null;

    public string $status = 'available';

    public ?float $purchasePrice = null;

    public string $currency = 'USD';

    public ?string $purchasedAt = null;

    public ?string $notes = null;

    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'tenantId'      => 'required|integer',
            'productId'     => 'required|integer',
            'variationId'   => 'nullable|integer',
            'batchId'       => 'nullable|integer',
            'serialNumber'  => 'required|string|max:255',
            'locationId'    => 'nullable|integer',
            'status'        => 'string|in:available,reserved,sold,returned,damaged,scrapped,in_transit',
            'purchasePrice' => 'nullable|numeric|min:0',
            'currency'      => 'string|size:3',
            'purchasedAt'   => 'nullable|date',
            'notes'         => 'nullable|string',
            'metadata'      => 'nullable|array',
        ];
    }
}
