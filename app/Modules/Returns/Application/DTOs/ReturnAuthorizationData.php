<?php

declare(strict_types=1);

namespace Modules\Returns\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class ReturnAuthorizationData extends BaseDto
{
    public int $tenantId;
    public string $rmaNumber;
    public string $returnType;
    public int $partyId;
    public string $partyType;
    public ?string $reason = null;
    public ?string $expiresAt = null;
    public ?string $notes = null;
    public ?array $metadata = null;
    public string $status = 'pending';

    public function rules(): array
    {
        return [
            'tenantId'   => 'required|integer',
            'rmaNumber'  => 'required|string|max:100',
            'returnType' => 'required|string|in:purchase_return,sales_return',
            'partyId'    => 'required|integer',
            'partyType'  => 'required|string|in:supplier,customer',
            'reason'     => 'nullable|string|max:255',
            'expiresAt'  => 'nullable|string',
            'notes'      => 'nullable|string',
            'metadata'   => 'nullable|array',
            'status'     => 'string|in:pending,approved,expired,cancelled',
        ];
    }
}
