<?php

declare(strict_types=1);

namespace Modules\Returns\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateCreditMemoData extends BaseDto
{
    public int $id;
    public ?string $notes = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'id'       => 'required|integer',
            'notes'    => 'sometimes|nullable|string',
            'metadata' => 'nullable|array',
        ];
    }
}
