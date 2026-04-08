<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

final class UnitOfMeasureData extends BaseDto
{
    public ?string $name = null;
    public ?string $abbreviation = null;
    public ?int $tenant_id = null;
    public ?string $type = null;
    public ?int $base_unit_id = null;
    public ?float $conversion_factor = null;
    public ?bool $is_base = null;
    public ?bool $is_active = null;

    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:100'],
            'abbreviation'      => ['required', 'string', 'max:20'],
            'tenant_id'         => ['sometimes', 'integer'],
            'type'              => ['sometimes', 'string', 'in:length,weight,volume,area,count,time,digital,other'],
            'base_unit_id'      => ['nullable', 'integer'],
            'conversion_factor' => ['sometimes', 'numeric'],
            'is_base'           => ['sometimes', 'boolean'],
            'is_active'         => ['sometimes', 'boolean'],
        ];
    }
}
