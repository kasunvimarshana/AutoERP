<?php

declare(strict_types=1);

namespace Modules\Returns\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class CreditMemoData extends BaseDto
{
    public int $tenantId;
    public string $referenceNumber;
    public int $partyId;
    public string $partyType;
    public ?int $stockReturnId = null;
    public float $amount = 0.0;
    public string $currency = 'USD';
    public ?string $notes = null;
    public ?array $metadata = null;
    public string $status = 'draft';

    public function rules(): array
    {
        return [
            'tenantId'        => 'required|integer',
            'referenceNumber' => 'required|string|max:100',
            'partyId'         => 'required|integer',
            'partyType'       => 'required|string|in:supplier,customer',
            'stockReturnId'   => 'nullable|integer',
            'amount'          => 'numeric|min:0',
            'currency'        => 'string|size:3',
            'notes'           => 'nullable|string',
            'metadata'        => 'nullable|array',
            'status'          => 'string|in:draft,issued,applied,voided',
        ];
    }
}
