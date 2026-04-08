<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

final class OrgUnitData extends BaseDto
{
    public ?int $tenant_id = null;
    public ?int $parent_id = null;
    public ?string $name = null;
    public ?string $code = null;
    public ?string $type = null;
    public ?string $description = null;
    public ?bool $is_active = null;
    public ?int $sort_order = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'tenant_id'   => ['required', 'integer', 'min:1'],
            'parent_id'   => ['nullable', 'integer', 'min:1'],
            'name'        => ['required', 'string', 'max:255'],
            'code'        => ['required', 'string', 'max:50'],
            'type'        => ['required', 'string', 'in:company,division,department,branch,warehouse,store,other'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['sometimes', 'boolean'],
            'sort_order'  => ['sometimes', 'integer', 'min:0'],
            'metadata'    => ['nullable', 'array'],
        ];
    }
}
