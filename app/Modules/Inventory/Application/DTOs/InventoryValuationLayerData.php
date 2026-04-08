<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class InventoryValuationLayerData extends BaseDto
{
    public int $tenantId;

    public int $productId;

    public ?int $variationId = null;

    public ?int $batchId = null;

    public ?int $locationId = null;

    public string $layerDate;

    public float $qtyIn;

    public float $unitCost;

    public string $currency = 'USD';

    public string $valuationMethod = 'fifo';

    public ?string $referenceType = null;

    public ?int $referenceId = null;

    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'tenantId'        => 'required|integer',
            'productId'       => 'required|integer',
            'variationId'     => 'nullable|integer',
            'batchId'         => 'nullable|integer',
            'locationId'      => 'nullable|integer',
            'layerDate'       => 'required|date',
            'qtyIn'           => 'required|numeric|min:0',
            'unitCost'        => 'required|numeric|min:0',
            'currency'        => 'string|size:3',
            'valuationMethod' => 'string|in:fifo,lifo,avco,standard_cost,specific_identification',
            'referenceType'   => 'nullable|string|max:100',
            'referenceId'     => 'nullable|integer',
            'metadata'        => 'nullable|array',
        ];
    }
}
