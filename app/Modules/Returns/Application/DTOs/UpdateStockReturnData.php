<?php

declare(strict_types=1);

namespace Modules\Returns\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateStockReturnData extends BaseDto
{
    public int $id;
    public ?string $returnReason = null;
    public ?string $notes = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'id'           => 'required|integer',
            'returnReason' => 'sometimes|nullable|string|max:255',
            'notes'        => 'sometimes|nullable|string',
            'metadata'     => 'nullable|array',
        ];
    }
}
