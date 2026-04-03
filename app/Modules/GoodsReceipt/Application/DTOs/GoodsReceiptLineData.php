<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class GoodsReceiptLineData extends BaseDto
{
    public int $tenantId;
    public int $goodsReceiptId;
    public int $lineNumber;
    public int $productId;
    public float $quantityReceived;
    public float $unitCost;
    public ?int $purchaseOrderLineId = null;
    public ?int $variationId = null;
    public ?int $batchId = null;
    public ?string $serialNumber = null;
    public ?int $uomId = null;
    public float $quantityExpected = 0.0;
    public float $quantityAccepted = 0.0;
    public float $quantityRejected = 0.0;
    public string $condition = 'good';
    public ?string $notes = null;
    public ?array $metadata = null;
    public string $status = 'pending';
    public ?int $putawayLocationId = null;

    public function rules(): array
    {
        return [
            'tenantId'           => 'required|integer',
            'goodsReceiptId'     => 'required|integer',
            'lineNumber'         => 'required|integer',
            'productId'          => 'required|integer',
            'quantityReceived'   => 'required|numeric|min:0',
            'unitCost'           => 'numeric|min:0',
            'purchaseOrderLineId'=> 'nullable|integer',
            'variationId'        => 'nullable|integer',
            'batchId'            => 'nullable|integer',
            'serialNumber'       => 'nullable|string|max:100',
            'uomId'              => 'nullable|integer',
            'quantityExpected'   => 'numeric|min:0',
            'quantityAccepted'   => 'numeric|min:0',
            'quantityRejected'   => 'numeric|min:0',
            'condition'          => 'string|in:good,damaged,expired,quarantine',
            'notes'              => 'nullable|string',
            'metadata'           => 'nullable|array',
            'status'             => 'string|in:pending,accepted,rejected,partially_accepted',
            'putawayLocationId'  => 'nullable|integer',
        ];
    }
}
