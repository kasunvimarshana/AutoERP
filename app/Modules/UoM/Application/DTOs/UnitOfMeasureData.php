<?php

declare(strict_types=1);

namespace Modules\UoM\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UnitOfMeasureData extends BaseDto
{
    public int $tenantId;

    public int $uomCategoryId;

    public string $name;

    public string $code;

    public string $symbol;

    public bool $isBaseUnit = false;

    public float $factor = 1.0;

    public ?string $description = null;

    public bool $isActive = true;

    public function rules(): array
    {
        return [
            'tenantId'      => 'required|integer|exists:tenants,id',
            'uomCategoryId' => 'required|integer|exists:uom_categories,id',
            'name'          => 'required|string|max:255',
            'code'          => 'required|string|max:50',
            'symbol'        => 'required|string|max:20',
            'isBaseUnit'    => 'boolean',
            'factor'        => 'numeric|min:0',
            'description'   => 'nullable|string',
            'isActive'      => 'boolean',
        ];
    }
}
