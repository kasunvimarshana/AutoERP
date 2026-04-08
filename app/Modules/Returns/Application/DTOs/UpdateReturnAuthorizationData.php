<?php

declare(strict_types=1);

namespace Modules\Returns\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateReturnAuthorizationData extends BaseDto
{
    public int $id;
    public ?string $reason = null;
    public ?string $notes = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'id'       => 'required|integer',
            'reason'   => 'sometimes|nullable|string|max:255',
            'notes'    => 'sometimes|nullable|string',
            'metadata' => 'nullable|array',
        ];
    }
}
