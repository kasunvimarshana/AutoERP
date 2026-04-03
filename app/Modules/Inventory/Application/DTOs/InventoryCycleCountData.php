<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class InventoryCycleCountData extends BaseDto
{
    public int $tenantId;

    public string $referenceNumber;

    public int $warehouseId;

    public ?int $zoneId = null;

    public ?int $locationId = null;

    public string $countMethod = 'manual';

    public string $status = 'draft';

    public ?int $assignedTo = null;

    public ?string $scheduledAt = null;

    public ?string $notes = null;

    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'tenantId'        => 'required|integer',
            'referenceNumber' => 'required|string|max:100',
            'warehouseId'     => 'required|integer',
            'zoneId'          => 'nullable|integer',
            'locationId'      => 'nullable|integer',
            'countMethod'     => 'string|in:abc,frequency,random,manual',
            'status'          => 'string|in:draft,in_progress,completed,cancelled',
            'assignedTo'      => 'nullable|integer',
            'scheduledAt'     => 'nullable|date',
            'notes'           => 'nullable|string',
            'metadata'        => 'nullable|array',
        ];
    }
}
