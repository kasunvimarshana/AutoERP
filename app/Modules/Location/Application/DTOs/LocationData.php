<?php

declare(strict_types=1);

namespace Modules\Location\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class LocationData extends BaseDto
{
    public int $tenant_id;

    public string $name;

    public string $type;

    public ?string $code;

    public ?string $description;

    public ?float $latitude;

    public ?float $longitude;

    public ?string $timezone;

    public ?array $metadata;

    public ?int $parent_id;

    public function rules(): array
    {
        return [
            'tenant_id'   => 'required|integer|exists:tenants,id',
            'name'        => 'required|string|max:255',
            'type'        => 'required|string|max:100',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'latitude'    => 'nullable|numeric|between:-90,90',
            'longitude'   => 'nullable|numeric|between:-180,180',
            'timezone'    => 'nullable|string|max:100',
            'metadata'    => 'nullable|array',
            'parent_id'   => 'nullable|integer|exists:locations,id',
        ];
    }
}
