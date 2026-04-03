<?php

declare(strict_types=1);

namespace Modules\Returns\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateStockReturnLineData extends BaseDto
{
    public int $id;
    public ?float $quantityApproved = null;
    public ?string $condition = null;
    public ?string $disposition = null;
    public ?string $notes = null;

    public function rules(): array
    {
        return [
            'id'               => 'required|integer',
            'quantityApproved' => 'sometimes|nullable|numeric|min:0',
            'condition'        => 'sometimes|nullable|string|in:good,damaged,defective,expired',
            'disposition'      => 'sometimes|nullable|string|in:restock,scrap,vendor_return,quarantine',
            'notes'            => 'sometimes|nullable|string',
        ];
    }
}
