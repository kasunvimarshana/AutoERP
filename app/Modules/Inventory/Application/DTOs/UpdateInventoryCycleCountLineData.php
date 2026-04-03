<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateInventoryCycleCountLineData extends BaseDto
{
    public int $id;

    public ?float $expectedQty = null;

    public ?int $locationId = null;

    public ?string $notes = null;

    public function rules(): array
    {
        return [
            'id'          => 'required|integer',
            'expectedQty' => 'sometimes|numeric|min:0',
            'locationId'  => 'sometimes|nullable|integer',
            'notes'       => 'sometimes|nullable|string',
        ];
    }
}
