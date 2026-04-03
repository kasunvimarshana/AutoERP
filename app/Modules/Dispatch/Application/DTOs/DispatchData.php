<?php

declare(strict_types=1);

namespace Modules\Dispatch\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class DispatchData extends BaseDto
{
    public int $tenantId;
    public string $referenceNumber;
    public int $warehouseId;
    public int $customerId;
    public string $dispatchDate;
    public ?int $salesOrderId = null;
    public ?string $customerReference = null;
    public ?string $estimatedDeliveryDate = null;
    public ?string $carrier = null;
    public ?string $notes = null;
    public ?array $metadata = null;
    public string $status = 'draft';
    public string $currency = 'USD';
    public ?float $totalWeight = null;

    public function rules(): array
    {
        return [
            'tenantId'             => 'required|integer',
            'referenceNumber'      => 'required|string|max:100',
            'warehouseId'          => 'required|integer',
            'customerId'           => 'required|integer',
            'dispatchDate'         => 'required|date',
            'salesOrderId'         => 'nullable|integer',
            'customerReference'    => 'nullable|string|max:100',
            'estimatedDeliveryDate'=> 'nullable|date',
            'carrier'              => 'nullable|string|max:100',
            'notes'                => 'nullable|string',
            'metadata'             => 'nullable|array',
            'status'               => 'string|in:draft,confirmed,in_transit,delivered,cancelled',
            'currency'             => 'string|size:3',
            'totalWeight'          => 'nullable|numeric|min:0',
        ];
    }
}
