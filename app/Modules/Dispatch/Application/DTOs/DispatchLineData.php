<?php

declare(strict_types=1);

namespace Modules\Dispatch\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class DispatchLineData extends BaseDto
{
    public int $tenantId;
    public int $dispatchId;
    public int $productId;
    public float $quantity;
    public ?int $salesOrderLineId = null;
    public ?int $productVariantId = null;
    public ?string $description = null;
    public ?string $unitOfMeasure = null;
    public ?int $warehouseLocationId = null;
    public ?string $batchNumber = null;
    public ?string $serialNumber = null;
    public string $status = 'pending';
    public ?float $weight = null;
    public ?string $notes = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'tenantId'           => 'required|integer',
            'dispatchId'         => 'required|integer',
            'productId'          => 'required|integer',
            'quantity'           => 'required|numeric|min:0',
            'salesOrderLineId'   => 'nullable|integer',
            'productVariantId'   => 'nullable|integer',
            'description'        => 'nullable|string',
            'unitOfMeasure'      => 'nullable|string|max:50',
            'warehouseLocationId'=> 'nullable|integer',
            'batchNumber'        => 'nullable|string|max:100',
            'serialNumber'       => 'nullable|string|max:100',
            'status'             => 'string|in:pending,picked,packed,shipped,cancelled',
            'weight'             => 'nullable|numeric|min:0',
            'notes'              => 'nullable|string',
            'metadata'           => 'nullable|array',
        ];
    }
}
