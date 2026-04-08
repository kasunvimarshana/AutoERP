<?php

declare(strict_types=1);

namespace Modules\Returns\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class StockReturnLineData extends BaseDto
{
    public int $tenantId;
    public int $stockReturnId;
    public int $productId;
    public float $quantityRequested;
    public ?int $variationId = null;
    public ?int $batchId = null;
    public ?int $serialNumberId = null;
    public ?int $uomId = null;
    public ?float $quantityApproved = null;
    public ?float $unitPrice = null;
    public ?float $unitCost = null;
    public string $condition = 'good';
    public string $disposition = 'restock';
    public ?string $notes = null;

    public function rules(): array
    {
        return [
            'tenantId'          => 'required|integer',
            'stockReturnId'     => 'required|integer',
            'productId'         => 'required|integer',
            'quantityRequested' => 'required|numeric|min:0',
            'variationId'       => 'nullable|integer',
            'batchId'           => 'nullable|integer',
            'serialNumberId'    => 'nullable|integer',
            'uomId'             => 'nullable|integer',
            'quantityApproved'  => 'nullable|numeric|min:0',
            'unitPrice'         => 'nullable|numeric|min:0',
            'unitCost'          => 'nullable|numeric|min:0',
            'condition'         => 'string|in:good,damaged,defective,expired',
            'disposition'       => 'string|in:restock,scrap,vendor_return,quarantine',
            'notes'             => 'nullable|string',
        ];
    }
}
