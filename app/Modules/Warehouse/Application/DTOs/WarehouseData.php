<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class WarehouseData extends BaseDto
{
    public int $tenant_id;

    public string $name;

    public string $type;

    public ?string $code;

    public ?string $description;

    public ?string $address;

    public ?float $capacity;

    public ?int $location_id;

    public ?array $metadata;

    public bool $is_active = true;

    public function rules(): array
    {
        return [
            'tenant_id'   => 'required|integer|exists:tenants,id',
            'name'        => 'required|string|max:255',
            'type'        => 'required|string|max:100',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'address'     => 'nullable|string|max:500',
            'capacity'    => 'nullable|numeric|min:0',
            'location_id' => 'nullable|integer|exists:locations,id',
            'metadata'    => 'nullable|array',
            'is_active'   => 'boolean',
        ];
    }
}
