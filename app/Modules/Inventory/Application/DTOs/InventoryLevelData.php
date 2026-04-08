<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class InventoryLevelData extends BaseDto
{
    public int $tenantId;

    public int $productId;

    public ?int $variationId = null;

    public ?int $locationId = null;

    public ?int $batchId = null;

    public ?int $uomId = null;

    public float $qtyOnHand = 0.0;

    public float $qtyReserved = 0.0;

    public float $qtyOnOrder = 0.0;

    public ?float $reorderPoint = null;

    public ?float $reorderQty = null;

    public ?float $maxQty = null;

    public ?float $minQty = null;

    public function rules(): array
    {
        return [
            'tenantId'    => 'required|integer',
            'productId'   => 'required|integer',
            'variationId' => 'nullable|integer',
            'locationId'  => 'nullable|integer',
            'batchId'     => 'nullable|integer',
            'uomId'       => 'nullable|integer',
            'qtyOnHand'   => 'numeric',
            'qtyReserved' => 'numeric|min:0',
            'qtyOnOrder'  => 'numeric|min:0',
            'reorderPoint'=> 'nullable|numeric|min:0',
            'reorderQty'  => 'nullable|numeric|min:0',
            'maxQty'      => 'nullable|numeric|min:0',
            'minQty'      => 'nullable|numeric|min:0',
        ];
    }
}
