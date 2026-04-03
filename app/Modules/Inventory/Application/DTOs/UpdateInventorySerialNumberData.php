<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateInventorySerialNumberData extends BaseDto
{
    public int $id;

    public ?int $locationId = null;

    public ?string $status = null;

    public ?float $purchasePrice = null;

    public ?string $currency = null;

    public ?string $purchasedAt = null;

    public ?string $soldAt = null;

    public ?string $returnedAt = null;

    public ?string $notes = null;

    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'id'            => 'required|integer',
            'locationId'    => 'sometimes|nullable|integer',
            'status'        => 'sometimes|string|in:available,reserved,sold,returned,damaged,scrapped,in_transit',
            'purchasePrice' => 'sometimes|nullable|numeric|min:0',
            'currency'      => 'sometimes|string|size:3',
            'purchasedAt'   => 'sometimes|nullable|date',
            'soldAt'        => 'sometimes|nullable|date',
            'returnedAt'    => 'sometimes|nullable|date',
            'notes'         => 'sometimes|nullable|string',
            'metadata'      => 'nullable|array',
        ];
    }
}
