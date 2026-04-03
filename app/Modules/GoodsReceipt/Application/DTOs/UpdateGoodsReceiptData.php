<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateGoodsReceiptData extends BaseDto
{
    public int $id;
    public ?string $notes = null;
    public ?array $metadata = null;
    public ?string $receivedDate = null;
    public ?int $warehouseId = null;

    public function rules(): array
    {
        return [
            'id'           => 'required|integer',
            'notes'        => 'nullable|string',
            'metadata'     => 'nullable|array',
            'receivedDate' => 'nullable|date',
            'warehouseId'  => 'nullable|integer',
        ];
    }
}
