<?php

declare(strict_types=1);

namespace Modules\UoM\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class ProductUomSettingData extends BaseDto
{
    public int $tenantId;

    public int $productId;

    public ?int $baseUomId = null;

    public ?int $purchaseUomId = null;

    public ?int $salesUomId = null;

    public ?int $inventoryUomId = null;

    public float $purchaseFactor = 1.0;

    public float $salesFactor = 1.0;

    public float $inventoryFactor = 1.0;

    public bool $isActive = true;

    public function rules(): array
    {
        return [
            'tenantId'       => 'required|integer|exists:tenants,id',
            'productId'      => 'required|integer',
            'baseUomId'      => 'nullable|integer|exists:units_of_measure,id',
            'purchaseUomId'  => 'nullable|integer|exists:units_of_measure,id',
            'salesUomId'     => 'nullable|integer|exists:units_of_measure,id',
            'inventoryUomId' => 'nullable|integer|exists:units_of_measure,id',
            'purchaseFactor' => 'numeric|min:0',
            'salesFactor'    => 'numeric|min:0',
            'inventoryFactor' => 'numeric|min:0',
            'isActive'       => 'boolean',
        ];
    }
}
