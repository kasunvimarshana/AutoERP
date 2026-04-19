<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class TenantDomainData extends BaseDto
{
    public int $tenant_id;

    public string $domain;

    public bool $is_primary = false;

    public bool $is_verified = false;

    public ?string $verified_at = null;

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'domain' => 'required|string|max:255',
            'is_primary' => 'required|boolean',
            'is_verified' => 'required|boolean',
            'verified_at' => 'nullable|date',
        ];
    }
}
