<?php

declare(strict_types=1);

namespace Modules\StockMovement\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateStockMovementData extends BaseDto
{
    public int $id;
    public ?string $status = null;
    public ?string $notes = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'id'       => 'required|integer',
            'status'   => 'sometimes|nullable|string|in:draft,confirmed,cancelled',
            'notes'    => 'sometimes|nullable|string',
            'metadata' => 'nullable|array',
        ];
    }
}
