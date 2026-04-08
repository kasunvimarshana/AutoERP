<?php

declare(strict_types=1);

namespace Modules\StockMovement\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class TransferStockData extends BaseDto
{
    public int $tenantId;

    public string $referenceNumber;

    public int $productId;

    public float $quantity;

    public int $fromLocationId;

    public int $toLocationId;

    public ?int $variationId = null;

    public ?int $batchId = null;

    public ?int $serialNumberId = null;

    public ?int $uomId = null;

    public ?float $unitCost = null;

    public string $currency = 'USD';

    public ?int $performedBy = null;

    public ?string $notes = null;

    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'tenantId'        => 'required|integer',
            'referenceNumber' => 'required|string|max:100',
            'productId'       => 'required|integer',
            'quantity'        => 'required|numeric|min:0.001',
            'fromLocationId'  => 'required|integer',
            'toLocationId'    => 'required|integer',
            'variationId'     => 'nullable|integer',
            'batchId'         => 'nullable|integer',
            'serialNumberId'  => 'nullable|integer',
            'uomId'           => 'nullable|integer',
            'unitCost'        => 'nullable|numeric|min:0',
            'currency'        => 'nullable|string|size:3',
            'performedBy'     => 'nullable|integer',
            'notes'           => 'nullable|string',
            'metadata'        => 'nullable|array',
        ];
    }
}
