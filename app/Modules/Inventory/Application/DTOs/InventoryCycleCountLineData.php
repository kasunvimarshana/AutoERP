<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class InventoryCycleCountLineData extends BaseDto
{
    public int $tenantId;

    public int $cycleCountId;

    public int $productId;

    public ?int $variationId = null;

    public ?int $batchId = null;

    public ?int $serialNumberId = null;

    public ?int $locationId = null;

    public float $expectedQty = 0.0;

    public ?string $notes = null;

    public function rules(): array
    {
        return [
            'tenantId'      => 'required|integer',
            'cycleCountId'  => 'required|integer',
            'productId'     => 'required|integer',
            'variationId'   => 'nullable|integer',
            'batchId'       => 'nullable|integer',
            'serialNumberId'=> 'nullable|integer',
            'locationId'    => 'nullable|integer',
            'expectedQty'   => 'numeric|min:0',
            'notes'         => 'nullable|string',
        ];
    }
}
