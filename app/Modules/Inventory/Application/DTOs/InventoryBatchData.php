<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class InventoryBatchData extends BaseDto
{
    public int $tenantId;

    public int $productId;

    public ?int $variationId = null;

    public string $batchNumber;

    public ?string $lotNumber = null;

    public ?string $manufactureDate = null;

    public ?string $expiryDate = null;

    public ?string $bestBeforeDate = null;

    public ?int $supplierId = null;

    public ?string $supplierBatchRef = null;

    public float $initialQty = 0.0;

    public float $unitCost = 0.0;

    public string $currency = 'USD';

    public string $status = 'active';

    public ?string $notes = null;

    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'tenantId'         => 'required|integer',
            'productId'        => 'required|integer',
            'variationId'      => 'nullable|integer',
            'batchNumber'      => 'required|string|max:100',
            'lotNumber'        => 'nullable|string|max:100',
            'manufactureDate'  => 'nullable|date',
            'expiryDate'       => 'nullable|date',
            'bestBeforeDate'   => 'nullable|date',
            'supplierId'       => 'nullable|integer',
            'supplierBatchRef' => 'nullable|string|max:255',
            'initialQty'       => 'numeric|min:0',
            'unitCost'         => 'numeric|min:0',
            'currency'         => 'string|size:3',
            'status'           => 'string|in:active,quarantine,expired,depleted,recalled',
            'notes'            => 'nullable|string',
            'metadata'         => 'nullable|array',
        ];
    }
}
