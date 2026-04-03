<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class GoodsReceiptData extends BaseDto
{
    public int $tenantId;
    public string $referenceNumber;
    public int $supplierId;
    public ?int $purchaseOrderId = null;
    public ?int $warehouseId = null;
    public ?string $receivedDate = null;
    public string $currency = 'USD';
    public ?string $notes = null;
    public ?array $metadata = null;
    public string $status = 'draft';
    public ?int $receivedBy = null;

    public function rules(): array
    {
        return [
            'tenantId'        => 'required|integer',
            'referenceNumber' => 'required|string|max:100',
            'supplierId'      => 'required|integer',
            'purchaseOrderId' => 'nullable|integer',
            'warehouseId'     => 'nullable|integer',
            'receivedDate'    => 'nullable|date',
            'currency'        => 'string|size:3',
            'notes'           => 'nullable|string',
            'metadata'        => 'nullable|array',
            'status'          => 'string|in:draft,pending,partially_received,fully_received,approved,cancelled',
            'receivedBy'      => 'nullable|integer',
        ];
    }
}
