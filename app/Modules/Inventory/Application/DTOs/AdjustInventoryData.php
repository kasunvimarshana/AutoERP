<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class AdjustInventoryData extends BaseDto
{
    public int $id;

    public float $adjustmentQty;

    public string $reason;

    public ?int $adjustedBy = null;

    public ?string $notes = null;

    public function rules(): array
    {
        return [
            'id'            => 'required|integer',
            'adjustmentQty' => 'required|numeric',
            'reason'        => 'required|string|max:255',
            'adjustedBy'    => 'nullable|integer',
            'notes'         => 'nullable|string',
        ];
    }
}
