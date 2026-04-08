<?php

declare(strict_types=1);

namespace Modules\UoM\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UomCategoryData extends BaseDto
{
    public int $tenantId;

    public string $name;

    public string $code;

    public ?string $description = null;

    public bool $isActive = true;

    public function rules(): array
    {
        return [
            'tenantId'    => 'required|integer|exists:tenants,id',
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50',
            'description' => 'nullable|string',
            'isActive'    => 'boolean',
        ];
    }
}
