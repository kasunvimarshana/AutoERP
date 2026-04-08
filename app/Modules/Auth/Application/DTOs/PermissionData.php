<?php

declare(strict_types=1);

namespace Modules\Auth\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

final class PermissionData extends BaseDto
{
    public string $name;

    public string $slug;

    public ?string $description = null;

    public ?string $module = null;

    public string $guard_name = 'api';

    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'module'      => ['nullable', 'string', 'max:100'],
            'guard_name'  => ['sometimes', 'string', 'max:50'],
            'metadata'    => ['nullable', 'array'],
        ];
    }
}
