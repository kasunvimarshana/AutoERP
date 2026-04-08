<?php

declare(strict_types=1);

namespace Modules\Returns\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class StockReturnData extends BaseDto
{
    public int $tenantId;
    public string $referenceNumber;
    public string $returnType;
    public int $partyId;
    public string $partyType;
    public ?int $originalReferenceId = null;
    public ?string $originalReferenceType = null;
    public ?string $returnReason = null;
    public float $totalAmount = 0.0;
    public string $currency = 'USD';
    public bool $restock = true;
    public ?int $restockLocationId = null;
    public float $restockingFee = 0.0;
    public ?string $notes = null;
    public ?array $metadata = null;
    public string $status = 'draft';

    public function rules(): array
    {
        return [
            'tenantId'              => 'required|integer',
            'referenceNumber'       => 'required|string|max:100',
            'returnType'            => 'required|string|in:purchase_return,sales_return',
            'partyId'               => 'required|integer',
            'partyType'             => 'required|string|in:supplier,customer',
            'originalReferenceId'   => 'nullable|integer',
            'originalReferenceType' => 'nullable|string|max:100',
            'returnReason'          => 'nullable|string|max:255',
            'totalAmount'           => 'numeric|min:0',
            'currency'              => 'string|size:3',
            'restock'               => 'boolean',
            'restockLocationId'     => 'nullable|integer',
            'restockingFee'         => 'numeric|min:0',
            'notes'                 => 'nullable|string',
            'metadata'              => 'nullable|array',
            'status'                => 'string|in:draft,pending_inspection,approved,rejected,completed,cancelled',
        ];
    }
}
