<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateGoodsReceiptLineData extends BaseDto
{
    public int $id;
    public float $quantityAccepted;
    public float $quantityRejected;
    public string $condition;
    public ?int $putawayLocationId = null;
    public ?string $notes = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'id'               => 'required|integer',
            'quantityAccepted' => 'required|numeric|min:0',
            'quantityRejected' => 'required|numeric|min:0',
            'condition'        => 'required|string|in:good,damaged,expired,quarantine',
            'putawayLocationId'=> 'nullable|integer',
            'notes'            => 'nullable|string',
            'metadata'         => 'nullable|array',
        ];
    }
}
