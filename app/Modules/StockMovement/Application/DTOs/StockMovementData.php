<?php

declare(strict_types=1);

namespace Modules\StockMovement\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class StockMovementData extends BaseDto
{
    public int $tenantId;
    public string $referenceNumber;
    public string $movementType;
    public int $productId;
    public float $quantity;
    public ?int $variationId = null;
    public ?int $fromLocationId = null;
    public ?int $toLocationId = null;
    public ?int $batchId = null;
    public ?int $serialNumberId = null;
    public ?int $uomId = null;
    public ?float $unitCost = null;
    public string $currency = 'USD';
    public ?string $referenceType = null;
    public ?int $referenceId = null;
    public ?int $performedBy = null;
    public ?string $movementDate = null;
    public ?string $notes = null;
    public ?array $metadata = null;
    public string $status = 'draft';

    public function rules(): array
    {
        return [
            'tenantId'        => 'required|integer',
            'referenceNumber' => 'required|string|max:100',
            'movementType'    => 'required|string|in:receipt,issue,transfer,adjustment,return_in,return_out',
            'productId'       => 'required|integer',
            'quantity'        => 'required|numeric',
            'variationId'     => 'nullable|integer',
            'fromLocationId'  => 'nullable|integer',
            'toLocationId'    => 'nullable|integer',
            'batchId'         => 'nullable|integer',
            'serialNumberId'  => 'nullable|integer',
            'uomId'           => 'nullable|integer',
            'unitCost'        => 'nullable|numeric|min:0',
            'currency'        => 'nullable|string|size:3',
            'referenceType'   => 'nullable|string|max:100',
            'referenceId'     => 'nullable|integer',
            'performedBy'     => 'nullable|integer',
            'movementDate'    => 'nullable|date',
            'notes'           => 'nullable|string',
            'metadata'        => 'nullable|array',
            'status'          => 'nullable|string|in:draft,confirmed,cancelled',
        ];
    }
}
