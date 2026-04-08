<?php

declare(strict_types=1);

namespace Modules\Auth\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

final class RoleData extends BaseDto
{
    public string $name;

    public string $slug;

    public ?int $tenant_id = null;

    public ?string $description = null;

    public bool $is_system = false;

    public string $guard_name = 'api';

    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100'],
            'slug'        => ['required', 'string', 'max:100'],
            'tenant_id'   => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'is_system'   => ['sometimes', 'boolean'],
            'guard_name'  => ['sometimes', 'string', 'max:50'],
            'metadata'    => ['nullable', 'array'],
        ];
    }
}
