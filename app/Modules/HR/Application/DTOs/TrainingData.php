<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class TrainingData extends BaseDto
{
    public int $tenant_id;

    public string $title;

    public string $start_date;

    public ?string $description = null;

    public ?string $trainer = null;

    public ?string $location = null;

    public ?string $end_date = null;

    public ?int $max_participants = null;

    public string $status = 'scheduled';

    public ?array $metadata = null;

    public bool $is_active = true;

    public function rules(): array
    {
        return [
            'tenant_id'        => 'required|integer',
            'title'            => 'required|string|max:255',
            'start_date'       => 'required|date_format:Y-m-d',
            'description'      => 'nullable|string',
            'trainer'          => 'nullable|string|max:255',
            'location'         => 'nullable|string|max:255',
            'end_date'         => 'nullable|date_format:Y-m-d',
            'max_participants' => 'nullable|integer|min:1',
            'status'           => 'nullable|string|in:scheduled,in_progress,completed,cancelled',
            'metadata'         => 'nullable|array',
            'is_active'        => 'nullable|boolean',
        ];
    }
}
