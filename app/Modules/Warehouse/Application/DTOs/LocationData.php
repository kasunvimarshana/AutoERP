<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;
use Modules\Warehouse\Domain\ValueObjects\LocationType;

final class LocationData extends BaseDto
{
    public int    $warehouse_id;
    public string $name;
    public string $code;
    public string $type      = LocationType::INTERNAL;
    public bool   $is_active = true;
    public ?int   $parent_id = null;
    public ?float $capacity  = null;
    public ?array $metadata  = null;

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'parent_id'    => ['nullable', 'integer', 'exists:locations,id'],
            'name'         => ['required', 'string', 'max:255'],
            'code'         => ['required', 'string', 'max:100'],
            'type'         => ['required', 'string', 'in:' . implode(',', LocationType::ALL)],
            'capacity'     => ['nullable', 'numeric', 'min:0'],
            'is_active'    => ['boolean'],
            'metadata'     => ['nullable', 'array'],
        ];
    }
}
