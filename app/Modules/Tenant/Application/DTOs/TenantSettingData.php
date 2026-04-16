<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class TenantSettingData extends BaseDto
{
    public int $tenant_id;

    public string $key;

    public ?array $value = null;

    public string $group = 'general';

    public bool $is_public = false;

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'key' => 'required|string|max:255',
            'value' => 'nullable|array',
            'group' => 'required|string|max:255',
            'is_public' => 'required|boolean',
        ];
    }
}
