<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateInventoryCycleCountData extends BaseDto
{
    public int $id;

    public ?int $zoneId = null;

    public ?int $locationId = null;

    public ?string $countMethod = null;

    public ?string $status = null;

    public ?int $assignedTo = null;

    public ?string $scheduledAt = null;

    public ?string $notes = null;

    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'id'          => 'required|integer',
            'zoneId'      => 'sometimes|nullable|integer',
            'locationId'  => 'sometimes|nullable|integer',
            'countMethod' => 'sometimes|string|in:abc,frequency,random,manual',
            'status'      => 'sometimes|string|in:draft,in_progress,completed,cancelled',
            'assignedTo'  => 'sometimes|nullable|integer',
            'scheduledAt' => 'sometimes|nullable|date',
            'notes'       => 'sometimes|nullable|string',
            'metadata'    => 'nullable|array',
        ];
    }
}
