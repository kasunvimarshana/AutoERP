<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class PositionData extends BaseDto
{
    public int $tenant_id;

    public string $name;

    public ?string $code = null;

    public ?string $description = null;

    public ?string $grade = null;

    public ?int $department_id = null;

    public ?array $metadata = null;

    public bool $is_active = true;

    public function rules(): array
    {
        return [
            'tenant_id'     => 'required|integer',
            'name'          => 'required|string|max:255',
            'code'          => 'nullable|string|max:50',
            'description'   => 'nullable|string',
            'grade'         => 'nullable|string|max:100',
            'department_id' => 'nullable|integer',
            'metadata'      => 'nullable|array',
            'is_active'     => 'boolean',
        ];
    }
}
