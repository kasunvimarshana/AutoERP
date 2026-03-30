<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class WarehouseZoneData extends BaseDto
{
    public int $warehouse_id;

    public int $tenant_id;

    public string $name;

    public string $type;

    public ?string $code;

    public ?string $description;

    public ?float $capacity;

    public ?array $metadata;

    public bool $is_active = true;

    public ?int $parent_zone_id;

    public function rules(): array
    {
        return [
            'warehouse_id'   => 'required|integer|exists:warehouses,id',
            'tenant_id'      => 'required|integer|exists:tenants,id',
            'name'           => 'required|string|max:255',
            'type'           => 'required|string|max:100',
            'code'           => 'nullable|string|max:50',
            'description'    => 'nullable|string',
            'capacity'       => 'nullable|numeric|min:0',
            'metadata'       => 'nullable|array',
            'is_active'      => 'boolean',
            'parent_zone_id' => 'nullable|integer|exists:warehouse_zones,id',
        ];
    }
}
